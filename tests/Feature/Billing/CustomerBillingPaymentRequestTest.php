<?php

namespace Tests\Feature\Billing;

use App\Models\BillingPaymentRequest;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerBillingPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_a_manual_payment_request(): void
    {
        Storage::fake('public');

        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $user = $this->makeCustomer();
        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();

        $response = $this->actingAs($user)->post(route('billing.payments.store'), [
            'plan_id' => $plan->id,
            'transaction_reference' => 'PP-REF-1001',
            'customer_note' => 'Submitted from the business PayPal account.',
            'receipt' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $paymentRequest = BillingPaymentRequest::query()->first();

        $response->assertRedirect(route('billing.payments.show', $paymentRequest));
        $this->assertNotNull($paymentRequest);
        $this->assertSame(BillingPaymentRequest::STATUS_PENDING, $paymentRequest->status);
        $this->assertSame($plan->id, $paymentRequest->plan_id);
        $this->assertSame($user->id, $paymentRequest->user_id);
        $this->assertSame(BillingPaymentRequest::GATEWAY_MANUAL, $paymentRequest->gateway);
        $this->assertTrue(Storage::disk('public')->exists($paymentRequest->receipt_path));
    }

    public function test_customer_cannot_create_a_second_open_payment_request(): void
    {
        Storage::fake('public');

        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $user = $this->makeCustomer();
        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();

        $existingRequest = BillingPaymentRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => 'usd',
            'amount' => $plan->price,
            'transaction_reference' => 'PH-REF-1001',
            'receipt_disk' => 'public',
            'receipt_path' => 'billing/payment-requests/existing.png',
            'receipt_name' => 'existing.png',
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('billing.payments.store'), [
            'plan_id' => $plan->id,
            'transaction_reference' => 'ST-REF-2001',
            'receipt' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $response->assertRedirect(route('billing.payments.show', $existingRequest));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('billing_payment_requests', 1);
    }

    public function test_customer_can_open_the_uploaded_receipt_through_the_billing_route(): void
    {
        Storage::fake('public');

        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $user = $this->makeCustomer();
        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();

        Storage::disk('public')->put('billing/payment-requests/existing.png', 'receipt-image-content');

        $paymentRequest = BillingPaymentRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => BillingPaymentRequest::GATEWAY_MANUAL,
            'currency' => 'usd',
            'amount' => $plan->price,
            'transaction_reference' => 'PP-REF-OPEN-1001',
            'receipt_disk' => 'public',
            'receipt_path' => 'billing/payment-requests/existing.png',
            'receipt_name' => 'existing.png',
            'status' => BillingPaymentRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('billing.payments.receipt', $paymentRequest))
            ->assertOk();
    }

    private function makeCustomer(): User
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        return $user;
    }
}
