<?php

namespace Tests\Feature\Billing;

use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManualSubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_a_manual_subscription_without_calling_stripe(): void
    {
        [$user, $subscription, $invoice] = $this->createManualSubscriptionFixture();

        $this->actingAs($user)
            ->post(route('billing.subscription.cancel'))
            ->assertRedirect(route('billing.plans'))
            ->assertSessionHas('success');

        $subscription->refresh();

        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->equalTo($invoice->period_end));
    }

    public function test_customer_can_resume_a_manual_subscription_in_grace_period_without_calling_stripe(): void
    {
        [$user, $subscription] = $this->createManualSubscriptionFixture(true);

        $this->actingAs($user)
            ->post(route('billing.subscription.resume'))
            ->assertRedirect(route('billing.plans'))
            ->assertSessionHas('success');

        $subscription->refresh();

        $this->assertNull($subscription->ends_at);
    }

    public function test_customer_is_prompted_to_use_manual_payment_requests_for_manual_subscription_plan_changes(): void
    {
        [$user] = $this->createManualSubscriptionFixture();
        $targetPlan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($user)
            ->post(route('billing.subscribe', $targetPlan))
            ->assertRedirect(route('billing.plans'))
            ->assertSessionHasErrors('billing');
    }

    private function createManualSubscriptionFixture(bool $onGracePeriod = false): array
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();
        $periodEnd = now()->addMonth();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'manual_payment_request_1',
            'stripe_status' => 'active',
            'stripe_price' => 'manual_plan_' . $plan->id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => $onGracePeriod ? $periodEnd->copy() : null,
            'plan_id' => $plan->id,
        ]);

        $invoice = BillingInvoice::query()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'number' => 'INV-MAN-TEST-0001',
            'currency' => 'usd',
            'amount_due' => 2900,
            'amount_paid' => 2900,
            'subtotal' => 2900,
            'tax' => 0,
            'status' => 'paid',
            'period_start' => now(),
            'period_end' => $periodEnd,
            'paid_at' => now(),
            'meta' => [
                'source' => 'manual_payment_request',
            ],
        ]);

        return [$user, $subscription, $invoice];
    }
}
