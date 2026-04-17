<?php

namespace App\Http\Controllers\Billing;

use App\Services\BillingStripeSyncService;
use App\Services\Notifications\BillingNotificationService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(
        private readonly BillingStripeSyncService $billingStripeSyncService,
        private readonly BillingNotificationService $billingNotificationService,
    ) {
        parent::__construct();
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        $this->billingStripeSyncService->syncSubscriptionPayload($payload['data']['object']);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        $this->billingStripeSyncService->syncSubscriptionPayload($payload['data']['object']);

        return $response;
    }

    protected function handleInvoiceFinalized(array $payload)
    {
        $invoice = $this->billingStripeSyncService->syncInvoicePayload($payload['data']['object']);
        if ($invoice) {
            $this->billingNotificationService->sendInvoiceAvailable($invoice);
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaid(array $payload)
    {
        $this->billingStripeSyncService->syncInvoicePayload($payload['data']['object'], 'paid');

        return $this->successMethod();
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        $invoice = $this->billingStripeSyncService->syncInvoicePayload($payload['data']['object'], 'failed');
        if ($invoice) {
            $this->billingNotificationService->sendFailedPayment($invoice);
        }

        return $this->successMethod();
    }

    protected function handleChargeSucceeded(array $payload)
    {
        $this->billingStripeSyncService->syncChargePayload($payload['data']['object']);

        return $this->successMethod();
    }

    protected function handleChargeRefunded(array $payload)
    {
        $this->billingStripeSyncService->syncRefundPayload($payload['data']['object']);

        return $this->successMethod();
    }
}
