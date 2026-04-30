<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingPaymentRequest;
use App\Models\BillingTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Notifications\BillingNotificationService;
use App\Services\Notifications\BillingPaymentRequestNotificationService;
use Illuminate\Support\Facades\DB;

class BillingPaymentRequestWorkflowService
{
    public function __construct(
        private readonly BillingPaymentRequestNotificationService $paymentRequestNotifications,
        private readonly BillingNotificationService $billingNotifications,
    ) {}

    public function approve(BillingPaymentRequest $paymentRequest, User $reviewer, ?string $adminNote = null): BillingPaymentRequest
    {
        $paymentRequest->loadMissing(['user', 'plan']);

        $approvedRequest = DB::transaction(function () use ($paymentRequest, $reviewer, $adminNote) {
            $plan = $paymentRequest->plan;
            $now = now();
            $amountPaid = (int) round((float) $paymentRequest->amount * 100);
            $periodEnd = $plan?->interval === 'year' ? $now->copy()->addYear() : $now->copy()->addMonth();

            $subscription = Subscription::query()
                ->where('user_id', $paymentRequest->user_id)
                ->where('type', 'default')
                ->latest('id')
                ->first();

            $subscriptionAttributes = [
                'user_id' => $paymentRequest->user_id,
                'type' => 'default',
                'stripe_id' => $subscription?->stripe_id ?: 'manual_payment_request_' . $paymentRequest->id,
                'stripe_status' => 'active',
                'stripe_price' => $plan?->stripe_price_id ?: 'manual_plan_' . $paymentRequest->plan_id,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
                'plan_id' => $paymentRequest->plan_id,
            ];

            if ($subscription) {
                $subscription->fill($subscriptionAttributes)->save();
            } else {
                $subscription = Subscription::query()->create($subscriptionAttributes);
            }

            $invoice = BillingInvoice::query()->create([
                'user_id' => $paymentRequest->user_id,
                'subscription_id' => $subscription->id,
                'number' => $this->invoiceNumber($paymentRequest),
                'currency' => strtolower((string) $paymentRequest->currency),
                'amount_due' => $amountPaid,
                'amount_paid' => $amountPaid,
                'subtotal' => $amountPaid,
                'tax' => 0,
                'status' => 'paid',
                'period_start' => $now,
                'period_end' => $periodEnd,
                'paid_at' => $now,
                'meta' => [
                    'source' => 'manual_payment_request',
                    'gateway' => $paymentRequest->gateway,
                    'payment_request_id' => $paymentRequest->id,
                ],
            ]);

            BillingTransaction::query()->create([
                'user_id' => $paymentRequest->user_id,
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'amount' => $amountPaid,
                'currency' => strtolower((string) $paymentRequest->currency),
                'type' => 'manual_payment',
                'status' => 'succeeded',
                'description' => sprintf(
                    'Approved manual payment for %s plan',
                    $plan?->name ?? 'selected'
                ),
                'processed_at' => $now,
                'meta' => [
                    'source' => 'manual_payment_request',
                    'payment_request_id' => $paymentRequest->id,
                ],
            ]);

            $paymentRequest->forceFill([
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'status' => BillingPaymentRequest::STATUS_APPROVED,
                'admin_note' => $adminNote,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => $now,
                'approved_at' => $now,
                'rejected_at' => null,
            ])->save();

            return $paymentRequest->fresh(['user', 'plan', 'invoice', 'reviewer']);
        });

        $this->paymentRequestNotifications->sendApproved($approvedRequest);
        if ($approvedRequest->invoice) {
            $this->billingNotifications->sendInvoiceAvailable($approvedRequest->invoice);
        }

        return $approvedRequest;
    }

    public function reject(BillingPaymentRequest $paymentRequest, User $reviewer, string $adminNote): BillingPaymentRequest
    {
        $paymentRequest->loadMissing(['user', 'plan']);

        $paymentRequest->forceFill([
            'status' => BillingPaymentRequest::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'approved_at' => null,
            'rejected_at' => now(),
        ])->save();

        $paymentRequest = $paymentRequest->fresh(['user', 'plan', 'reviewer']);
        $this->paymentRequestNotifications->sendRejected($paymentRequest);

        return $paymentRequest;
    }

    private function invoiceNumber(BillingPaymentRequest $paymentRequest): string
    {
        return sprintf('INV-MAN-%s-%04d', now()->format('Ym'), $paymentRequest->id);
    }
}
