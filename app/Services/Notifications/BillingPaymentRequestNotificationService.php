<?php

namespace App\Services\Notifications;

use App\Models\BillingPaymentRequest;
use App\Notifications\BillingPaymentRequestNotification;

class BillingPaymentRequestNotificationService
{
    public function __construct(private readonly NotificationDispatchService $dispatchService) {}

    public function sendSubmitted(BillingPaymentRequest $paymentRequest): bool
    {
        $paymentRequest->loadMissing('plan', 'user');

        return $this->dispatchService->sendOnce(
            $paymentRequest->user,
            new BillingPaymentRequestNotification(
                BillingPaymentRequestNotification::TYPE_SUBMITTED,
                'Payment submitted for review',
                sprintf(
                    'Your payment request for the %s plan has been submitted and is waiting for billing review.',
                    $paymentRequest->plan?->name ?? 'selected'
                ),
                $paymentRequest,
                route('billing.payments.show', $paymentRequest),
                'Track Request',
                ['status' => $paymentRequest->status]
            ),
            sprintf('billing:payment_request:submitted:%s:%s', $paymentRequest->id, optional($paymentRequest->submitted_at)->timestamp ?: $paymentRequest->updated_at?->timestamp),
            ['payment_request_id' => $paymentRequest->id]
        );
    }

    public function sendResubmitted(BillingPaymentRequest $paymentRequest): bool
    {
        $paymentRequest->loadMissing('plan', 'user');

        return $this->dispatchService->sendOnce(
            $paymentRequest->user,
            new BillingPaymentRequestNotification(
                BillingPaymentRequestNotification::TYPE_RESUBMITTED,
                'Payment request resubmitted',
                sprintf(
                    'Your updated payment request for the %s plan was resubmitted and is waiting for review again.',
                    $paymentRequest->plan?->name ?? 'selected'
                ),
                $paymentRequest,
                route('billing.payments.show', $paymentRequest),
                'Open Request',
                ['status' => $paymentRequest->status]
            ),
            sprintf('billing:payment_request:resubmitted:%s:%s', $paymentRequest->id, optional($paymentRequest->submitted_at)->timestamp ?: $paymentRequest->updated_at?->timestamp),
            ['payment_request_id' => $paymentRequest->id]
        );
    }

    public function sendApproved(BillingPaymentRequest $paymentRequest): bool
    {
        $paymentRequest->loadMissing('plan', 'user', 'invoice');

        $actionUrl = $paymentRequest->invoice
            ? route('billing.invoices.download', $paymentRequest->invoice->id)
            : route('billing.payments.show', $paymentRequest);

        $actionText = $paymentRequest->invoice ? 'Download Invoice' : 'View Request';

        return $this->dispatchService->sendOnce(
            $paymentRequest->user,
            new BillingPaymentRequestNotification(
                BillingPaymentRequestNotification::TYPE_APPROVED,
                'Payment approved and plan activated',
                sprintf(
                    'Your payment request for the %s plan was approved. Your billing access is now active.',
                    $paymentRequest->plan?->name ?? 'selected'
                ),
                $paymentRequest,
                $actionUrl,
                $actionText,
                ['status' => $paymentRequest->status]
            ),
            'billing:payment_request:approved:' . $paymentRequest->id,
            ['payment_request_id' => $paymentRequest->id]
        );
    }

    public function sendRejected(BillingPaymentRequest $paymentRequest): bool
    {
        $paymentRequest->loadMissing('plan', 'user');

        return $this->dispatchService->sendOnce(
            $paymentRequest->user,
            new BillingPaymentRequestNotification(
                BillingPaymentRequestNotification::TYPE_REJECTED,
                'Payment request needs changes',
                sprintf(
                    'Your payment request for the %s plan was rejected. Review the billing note and resubmit corrected details.',
                    $paymentRequest->plan?->name ?? 'selected'
                ),
                $paymentRequest,
                route('billing.payments.show', $paymentRequest),
                'Review Request',
                ['status' => $paymentRequest->status]
            ),
            'billing:payment_request:rejected:' . $paymentRequest->id,
            ['payment_request_id' => $paymentRequest->id]
        );
    }
}
