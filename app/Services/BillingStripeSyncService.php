<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class BillingStripeSyncService
{
    public function syncSubscriptionPayload(array $payload): ?Subscription
    {
        $user = $this->resolveUser(data_get($payload, 'customer'));
        if (!$user) {
            return null;
        }

        $subscriptionId = (string) data_get($payload, 'id');
        $subscription = $user->subscriptions()->where('stripe_id', $subscriptionId)->first();

        if (!$subscription) {
            $subscription = $user->subscriptions()->where('type', 'default')->latest('id')->first();
        }

        if (!$subscription) {
            $subscription = new Subscription(['user_id' => $user->id, 'type' => 'default']);
        }

        $plan = $this->resolvePlanFromSubscriptionPayload($payload);

        $cancelAt = $this->timestamp(data_get($payload, 'cancel_at'));
        $canceledAt = $this->timestamp(data_get($payload, 'canceled_at'));
        $endsAt = data_get($payload, 'cancel_at_period_end')
            ? ($cancelAt ?: $canceledAt)
            : $canceledAt;

        $subscription->forceFill([
            'user_id' => $user->id,
            'type' => $subscription->type ?: 'default',
            'stripe_id' => $subscriptionId,
            'stripe_status' => (string) data_get($payload, 'status', 'incomplete'),
            'stripe_price' => (string) data_get($payload, 'items.data.0.price.id', data_get($payload, 'plan.id', '')),
            'quantity' => (int) data_get($payload, 'items.data.0.quantity', data_get($payload, 'quantity', 1)),
            'trial_ends_at' => $this->timestamp(data_get($payload, 'trial_end')),
            'ends_at' => $endsAt,
            'plan_id' => $plan?->id,
        ])->save();

        return $subscription->fresh(['plan']);
    }

    public function syncCheckoutSession(string $checkoutSessionId, User $user): ?Subscription
    {
        if (blank(config('cashier.secret')) || blank($checkoutSessionId)) {
            return null;
        }

        try {
            $session = $this->stripeClient()->checkout->sessions->retrieve($checkoutSessionId, [
                'expand' => [
                    'subscription',
                    'subscription.latest_invoice',
                    'subscription.items.data.price',
                ],
            ])->toArray();
        } catch (ApiErrorException) {
            return null;
        }

        $sessionCustomerId = data_get($session, 'customer');
        if (filled($sessionCustomerId) && filled($user->stripe_id) && $user->stripe_id !== $sessionCustomerId) {
            return null;
        }

        if (filled($sessionCustomerId) && $user->stripe_id !== $sessionCustomerId) {
            $user->forceFill(['stripe_id' => $sessionCustomerId])->save();
            $user->refresh();
        }

        $subscriptionPayload = data_get($session, 'subscription');
        if (!$subscriptionPayload) {
            return null;
        }

        if (is_string($subscriptionPayload)) {
            try {
                $subscriptionPayload = $this->stripeClient()->subscriptions->retrieve($subscriptionPayload, [
                    'expand' => [
                        'latest_invoice',
                        'items.data.price',
                    ],
                ])->toArray();
            } catch (ApiErrorException) {
                return null;
            }
        }

        $subscription = $this->syncSubscriptionPayload($subscriptionPayload);
        if (!$subscription) {
            return null;
        }

        $invoicePayload = data_get($subscriptionPayload, 'latest_invoice');
        if (is_array($invoicePayload)) {
            $this->syncInvoicePayload($invoicePayload);
        }

        return $subscription;
    }

    public function syncInvoicePayload(array $payload, ?string $forcedStatus = null): ?BillingInvoice
    {
        $user = $this->resolveUser(data_get($payload, 'customer'));
        if (!$user) {
            return null;
        }

        $subscription = null;
        if ($subscriptionStripeId = data_get($payload, 'subscription')) {
            $subscription = $user->subscriptions()->where('stripe_id', $subscriptionStripeId)->first();
        }

        if ($subscription && !$subscription->plan_id) {
            $plan = $this->resolvePlanFromPriceId(data_get($payload, 'lines.data.0.price.id'));
            if ($plan) {
                $subscription->forceFill(['plan_id' => $plan->id])->save();
            }
        }

        $invoice = BillingInvoice::updateOrCreate(
            ['stripe_invoice_id' => data_get($payload, 'id')],
            [
                'user_id' => $user->id,
                'subscription_id' => $subscription?->id,
                'number' => data_get($payload, 'number'),
                'currency' => (string) data_get($payload, 'currency', 'usd'),
                'amount_due' => (int) data_get($payload, 'amount_due', 0),
                'amount_paid' => (int) data_get($payload, 'amount_paid', 0),
                'subtotal' => data_get($payload, 'subtotal') !== null ? (int) data_get($payload, 'subtotal') : null,
                'tax' => data_get($payload, 'tax') !== null ? (int) data_get($payload, 'tax') : null,
                'status' => $forcedStatus ?: (string) data_get($payload, 'status', 'open'),
                'invoice_pdf_url' => data_get($payload, 'invoice_pdf'),
                'hosted_invoice_url' => data_get($payload, 'hosted_invoice_url'),
                'period_start' => $this->timestamp(data_get($payload, 'period_start')),
                'period_end' => $this->timestamp(data_get($payload, 'period_end')),
                'paid_at' => $this->timestamp(data_get($payload, 'status_transitions.paid_at')),
                'meta' => $payload,
            ]
        );

        $chargeId = data_get($payload, 'charge');
        $paymentIntentId = data_get($payload, 'payment_intent');

        if ($chargeId || $paymentIntentId) {
            BillingTransaction::updateOrCreate(
                [
                    'stripe_charge_id' => $chargeId ?: null,
                    'stripe_payment_intent_id' => $paymentIntentId ?: null,
                ],
                [
                    'user_id' => $user->id,
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $subscription?->id,
                    'amount' => (int) data_get($payload, 'amount_paid', data_get($payload, 'amount_due', 0)),
                    'currency' => (string) data_get($payload, 'currency', 'usd'),
                    'type' => 'invoice',
                    'status' => $invoice->status,
                    'description' => 'Invoice ' . (data_get($payload, 'number') ?: data_get($payload, 'id')),
                    'processed_at' => $invoice->paid_at,
                    'meta' => [
                        'stripe_invoice_id' => data_get($payload, 'id'),
                    ],
                ]
            );
        }

        return $invoice;
    }

    public function syncChargePayload(array $payload): ?BillingTransaction
    {
        $user = $this->resolveUser(data_get($payload, 'customer'));
        if (!$user) {
            return null;
        }

        return BillingTransaction::updateOrCreate(
            ['stripe_charge_id' => data_get($payload, 'id')],
            [
                'user_id' => $user->id,
                'invoice_id' => BillingInvoice::query()
                    ->where('stripe_invoice_id', data_get($payload, 'invoice'))
                    ->value('id'),
                'subscription_id' => $user->subscriptions()
                    ->where('stripe_id', data_get($payload, 'invoice.subscription'))
                    ->value('id'),
                'stripe_payment_intent_id' => data_get($payload, 'payment_intent'),
                'amount' => (int) data_get($payload, 'amount', 0),
                'currency' => (string) data_get($payload, 'currency', 'usd'),
                'type' => 'charge',
                'status' => (string) data_get($payload, 'status', 'succeeded'),
                'description' => data_get($payload, 'description'),
                'processed_at' => $this->timestamp(data_get($payload, 'created')),
                'meta' => [
                    'refunded' => (bool) data_get($payload, 'refunded', false),
                ],
            ]
        );
    }

    public function syncRefundPayload(array $payload): ?BillingTransaction
    {
        $user = $this->resolveUser(data_get($payload, 'customer'));
        if (!$user) {
            return null;
        }

        return BillingTransaction::updateOrCreate(
            ['stripe_charge_id' => data_get($payload, 'id')],
            [
                'user_id' => $user->id,
                'invoice_id' => BillingInvoice::query()
                    ->where('stripe_invoice_id', data_get($payload, 'invoice'))
                    ->value('id'),
                'subscription_id' => null,
                'stripe_payment_intent_id' => data_get($payload, 'payment_intent'),
                'amount' => (int) data_get($payload, 'amount_refunded', 0),
                'currency' => (string) data_get($payload, 'currency', 'usd'),
                'type' => 'refund',
                'status' => 'refunded',
                'description' => data_get($payload, 'description') ?: 'Stripe refund',
                'processed_at' => $this->timestamp(data_get($payload, 'created')),
                'meta' => [
                    'refunded' => true,
                ],
            ]
        );
    }

    private function resolveUser(?string $stripeCustomerId): ?User
    {
        if (!$stripeCustomerId) {
            return null;
        }

        return User::query()->where('stripe_id', $stripeCustomerId)->first();
    }

    private function resolvePlanFromSubscriptionPayload(array $payload): ?Plan
    {
        $planId = data_get($payload, 'metadata.plan_id');
        if ($planId) {
            return Plan::query()->find($planId);
        }

        return $this->resolvePlanFromPriceId(data_get($payload, 'items.data.0.price.id'));
    }

    private function resolvePlanFromPriceId(?string $priceId): ?Plan
    {
        if (!$priceId) {
            return null;
        }

        return Plan::query()->where('stripe_price_id', $priceId)->first();
    }

    private function timestamp(null|int|string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return is_numeric($value)
            ? Carbon::createFromTimestamp((int) $value)
            : Carbon::parse((string) $value);
    }

    private function stripeClient(): StripeClient
    {
        return new StripeClient((string) config('cashier.secret'));
    }
}
