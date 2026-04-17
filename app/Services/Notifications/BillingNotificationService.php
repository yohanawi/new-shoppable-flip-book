<?php

namespace App\Services\Notifications;

use App\Models\BillingInvoice;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BillingReminderNotification;
use Carbon\CarbonInterface;

class BillingNotificationService
{
    public function __construct(private readonly NotificationDispatchService $dispatchService) {}

    public function sendInvoiceAvailable(BillingInvoice $invoice): bool
    {
        $invoice->loadMissing(['user', 'subscription.plan']);

        if (!$invoice->user) {
            return false;
        }

        $actionUrl = $invoice->hosted_invoice_url
            ?: route('billing.invoices.download', $invoice->stripe_invoice_id ?: (string) $invoice->id);

        return $this->dispatchService->sendOnce(
            $invoice->user,
            new BillingReminderNotification(
                BillingReminderNotification::TYPE_INVOICE_AVAILABLE,
                'Your latest invoice is ready',
                'A new invoice is available for your subscription' . $this->invoiceAmountSuffix($invoice) . '.',
                $actionUrl,
                'View Invoice',
                [
                    'invoice_number' => $invoice->number,
                    'amount_due' => $invoice->amount_due,
                    'currency' => $invoice->currency,
                ],
                $invoice,
                $invoice->subscription,
            ),
            'billing:invoice_available:' . ($invoice->stripe_invoice_id ?: $invoice->id),
            ['invoice_id' => $invoice->id]
        );
    }

    public function sendFailedPayment(BillingInvoice $invoice): bool
    {
        $invoice->loadMissing(['user', 'subscription.plan']);

        if (!$invoice->user) {
            return false;
        }

        return $this->dispatchService->sendOnce(
            $invoice->user,
            new BillingReminderNotification(
                BillingReminderNotification::TYPE_FAILED_PAYMENT,
                'Payment failed for your subscription',
                'We could not process your latest payment' . $this->invoiceAmountSuffix($invoice) . '. Update your payment method to avoid service interruption.',
                route('billing.index'),
                'Review Billing',
                [
                    'invoice_number' => $invoice->number,
                    'amount_due' => $invoice->amount_due,
                    'currency' => $invoice->currency,
                ],
                $invoice,
                $invoice->subscription,
            ),
            'billing:failed_payment:' . ($invoice->stripe_invoice_id ?: $invoice->id),
            ['invoice_id' => $invoice->id]
        );
    }

    public function sendScheduledReminders(array $dayOffsets = [7, 3, 1]): array
    {
        return [
            'upcoming_payment' => $this->sendUpcomingPaymentReminders($dayOffsets),
            'trial_ending' => $this->sendTrialEndingReminders($dayOffsets),
        ];
    }

    public function sendUpcomingPaymentReminders(array $dayOffsets = [7, 3, 1]): int
    {
        $offsets = $this->normalizeDayOffsets($dayOffsets);
        $sentCount = 0;

        $subscriptions = Subscription::query()
            ->with('plan')
            ->where('stripe_status', 'active')
            ->whereNull('ends_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $this->resolveSubscriptionUser($subscription);
            $invoice = $subscription->invoiceRecords()
                ->where('status', 'paid')
                ->whereNotNull('period_end')
                ->latest('period_end')
                ->first();

            if (!$invoice?->period_end || !$user) {
                continue;
            }

            $daysUntil = (int) now()->startOfDay()->diffInDays($invoice->period_end->copy()->startOfDay(), false);
            if (!in_array($daysUntil, $offsets, true)) {
                continue;
            }

            $sent = $this->dispatchService->sendOnce(
                $user,
                new BillingReminderNotification(
                    BillingReminderNotification::TYPE_UPCOMING_PAYMENT,
                    'Upcoming subscription payment reminder',
                    $this->upcomingPaymentMessage($subscription, $invoice->period_end, $daysUntil),
                    route('billing.index'),
                    'Open Billing',
                    [
                        'days_until_due' => $daysUntil,
                        'renewal_date' => $invoice->period_end->toDateString(),
                        'plan_name' => $subscription->plan?->name,
                    ],
                    $invoice,
                    $subscription,
                ),
                sprintf(
                    'billing:upcoming_payment:%s:%s:%s',
                    $subscription->id,
                    $daysUntil,
                    $invoice->period_end->toDateString()
                ),
                [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'days_until_due' => $daysUntil,
                ]
            );

            if ($sent) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    public function sendTrialEndingReminders(array $dayOffsets = [7, 3, 1]): int
    {
        $offsets = $this->normalizeDayOffsets($dayOffsets);
        $sentCount = 0;

        $subscriptions = Subscription::query()
            ->with('plan')
            ->where('stripe_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $this->resolveSubscriptionUser($subscription);

            if (!$user || !$subscription->trial_ends_at) {
                continue;
            }

            $daysUntil = (int) now()->startOfDay()->diffInDays($subscription->trial_ends_at->copy()->startOfDay(), false);
            if (!in_array($daysUntil, $offsets, true)) {
                continue;
            }

            $sent = $this->dispatchService->sendOnce(
                $user,
                new BillingReminderNotification(
                    BillingReminderNotification::TYPE_TRIAL_ENDING,
                    'Your trial is ending soon',
                    $this->trialEndingMessage($subscription, $daysUntil),
                    route('billing.index'),
                    'Review Plan',
                    [
                        'days_until_due' => $daysUntil,
                        'trial_ends_at' => $subscription->trial_ends_at->toDateString(),
                        'plan_name' => $subscription->plan?->name,
                    ],
                    null,
                    $subscription,
                ),
                sprintf(
                    'billing:trial_ending:%s:%s:%s',
                    $subscription->id,
                    $daysUntil,
                    $subscription->trial_ends_at->toDateString()
                ),
                [
                    'subscription_id' => $subscription->id,
                    'days_until_due' => $daysUntil,
                ]
            );

            if ($sent) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function normalizeDayOffsets(array $dayOffsets): array
    {
        return collect($dayOffsets)
            ->map(fn($days) => max(0, (int) $days))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function upcomingPaymentMessage(Subscription $subscription, CarbonInterface $renewalDate, int $daysUntil): string
    {
        $planName = $subscription->plan?->name ?: 'subscription';
        $dayLabel = $daysUntil === 1 ? '1 day' : $daysUntil . ' days';

        return sprintf(
            'Your %s renews in %s on %s. Make sure your payment method is up to date.',
            $planName,
            $dayLabel,
            $renewalDate->format('M d, Y')
        );
    }

    private function trialEndingMessage(Subscription $subscription, int $daysUntil): string
    {
        $planName = $subscription->plan?->name ?: 'subscription';
        $dayLabel = $daysUntil === 1 ? '1 day' : $daysUntil . ' days';

        return sprintf(
            'Your %s trial ends in %s. Review your plan before billing starts.',
            $planName,
            $dayLabel
        );
    }

    private function invoiceAmountSuffix(BillingInvoice $invoice): string
    {
        if ((int) $invoice->amount_due <= 0) {
            return '';
        }

        return ' for ' . strtoupper((string) $invoice->currency) . ' ' . number_format($invoice->amount_due / 100, 2);
    }

    private function resolveSubscriptionUser(Subscription $subscription): ?User
    {
        return User::query()->find($subscription->user_id);
    }
}
