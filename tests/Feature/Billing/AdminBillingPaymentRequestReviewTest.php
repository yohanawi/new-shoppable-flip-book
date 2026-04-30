<?php

namespace Tests\Feature\Billing;

use App\Models\BillingPaymentRequest;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\BillingPaymentRequestNotification;
use App\Notifications\BillingReminderNotification;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBillingPaymentRequestReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_payment_request_and_customer_can_download_manual_invoice(): void
    {
        Storage::fake('public');

        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();

        $paymentRequest = BillingPaymentRequest::query()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => 'usd',
            'amount' => $plan->price,
            'transaction_reference' => 'PAYPAL-APPROVE-1001',
            'receipt_disk' => 'public',
            'receipt_path' => UploadedFile::fake()->image('receipt.png')->store('billing/payment-requests', 'public'),
            'receipt_name' => 'receipt.png',
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.billing.payment-requests.review', $paymentRequest), [
            'review_action' => 'approve',
            'admin_note' => 'Payment verified by the billing team.',
        ]);

        $paymentRequest->refresh();
        $invoice = $paymentRequest->invoice()->first();

        $response->assertRedirect(route('admin.billing.index'));
        $this->assertSame(BillingPaymentRequest::STATUS_APPROVED, $paymentRequest->status);
        $this->assertNotNull($paymentRequest->subscription_id);
        $this->assertNotNull($paymentRequest->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $paymentRequest->subscription_id,
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'stripe_status' => 'active',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'succeeded',
            'type' => 'manual_payment',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $customer->id,
            'type' => BillingPaymentRequestNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $customer->id,
            'type' => BillingReminderNotification::class,
        ]);

        $invoiceResponse = $this->actingAs($customer)->get(route('billing.invoices.download', $invoice->id));
        $invoiceResponse->assertOk();
        $invoiceResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_can_resubmit_after_admin_rejects_payment_request(): void
    {
        Storage::fake('public');

        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();

        $paymentRequest = BillingPaymentRequest::query()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => 'usd',
            'amount' => $plan->price,
            'transaction_reference' => 'PAYHERE-REJECT-1001',
            'receipt_disk' => 'public',
            'receipt_path' => UploadedFile::fake()->image('receipt.png')->store('billing/payment-requests', 'public'),
            'receipt_name' => 'receipt.png',
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.billing.payment-requests.review', $paymentRequest), [
            'review_action' => 'reject',
            'admin_note' => 'Receipt is unclear. Upload a sharper copy and confirm the transfer reference.',
        ])->assertRedirect(route('admin.billing.index'));

        $paymentRequest->refresh();
        $this->assertSame(BillingPaymentRequest::STATUS_REJECTED, $paymentRequest->status);

        $response = $this->actingAs($customer)->post(route('billing.payments.resubmit', $paymentRequest), [
            'plan_id' => $plan->id,
            'transaction_reference' => 'PAYHERE-RETRY-2001',
            'customer_note' => 'Updated receipt attached with the corrected transfer ID.',
            'receipt' => UploadedFile::fake()->image('receipt-updated.png'),
        ]);

        $paymentRequest->refresh();

        $response->assertRedirect(route('billing.payments.show', $paymentRequest));
        $this->assertSame(BillingPaymentRequest::STATUS_PENDING, $paymentRequest->status);
        $this->assertNull($paymentRequest->admin_note);
        $this->assertSame('PAYHERE-RETRY-2001', $paymentRequest->transaction_reference);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
