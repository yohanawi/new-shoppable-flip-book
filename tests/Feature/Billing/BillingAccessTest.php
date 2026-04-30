<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingStripeSyncService;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BillingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_billing_dashboard(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertOk();
        $response->assertSee('Billing Overview');
        $response->assertSee('Current Plan');
    }

    public function test_customer_can_open_billing_sections(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('billing.plans'))
            ->assertOk()
            ->assertSee('Plans and Subscription');

        $this->actingAs($user)
            ->get(route('billing.payment-methods.index'))
            ->assertOk()
            ->assertSee('Payment Methods');

        $this->actingAs($user)
            ->get(route('billing.invoices.index'))
            ->assertOk()
            ->assertSee('Invoices and Activity');
    }

    public function test_successful_checkout_return_activates_subscription_and_redirects_with_success_message(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
            'stripe_id' => 'cus_test_checkout_success',
        ]);
        $user->assignRole('customer');

        $plan = Plan::query()->where('slug', 'basic')->firstOrFail();
        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => 'sub_checkout_success',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id ?: 'price_basic',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->mock(BillingStripeSyncService::class, function ($mock) use ($subscription, $user) {
            $mock->shouldReceive('syncCheckoutSession')
                ->once()
                ->with('cs_test_success', Mockery::on(fn(User $resolvedUser) => $resolvedUser->is($user)))
                ->andReturn($subscription);
        });

        $this->actingAs($user)
            ->get(route('billing.plans', ['checkout' => 'success', 'session_id' => 'cs_test_success']))
            ->assertRedirect(route('billing.plans'))
            ->assertSessionHas('success', 'Payment successful. Your subscription is now active.');
    }

    public function test_checkout_return_shows_failed_message_when_local_activation_cannot_be_confirmed(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
            'stripe_id' => 'cus_test_checkout_failed',
        ]);
        $user->assignRole('customer');

        $this->mock(BillingStripeSyncService::class, function ($mock) use ($user) {
            $mock->shouldReceive('syncCheckoutSession')
                ->once()
                ->with('cs_test_failed', Mockery::on(fn(User $resolvedUser) => $resolvedUser->is($user)))
                ->andReturn(null);
        });

        $this->actingAs($user)
            ->get(route('billing.plans', ['checkout' => 'success', 'session_id' => 'cs_test_failed']))
            ->assertRedirect(route('billing.plans', ['checkout' => 'failed']))
            ->assertSessionHasErrors('billing');

        $this->actingAs($user)
            ->get(route('billing.plans', ['checkout' => 'failed']))
            ->assertOk()
            ->assertSee('Payment could not be activated automatically.');
    }

    public function test_customer_billing_dashboard_renders_when_plans_are_not_seeded(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertOk();
        $response->assertSee('Billing plans have not been seeded yet.');
        $response->assertSee('Manage Plans');
    }

    public function test_admin_can_open_admin_billing_dashboard(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('admin.billing.index'));

        $response->assertOk();
        $response->assertSee('Billing Dashboard');
        $response->assertSee('Plan Management');
    }

    public function test_free_customer_is_blocked_from_analytics_but_paid_customer_can_access_it(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $freeUser */
        $freeUser = User::factory()->create([
            'role' => 'customer',
        ]);
        $freeUser->assignRole('customer');

        $freeResponse = $this->actingAs($freeUser)->get(route('analytics.index'));
        $freeResponse->assertForbidden();

        /** @var User $paidUser */
        $paidUser = User::factory()->create([
            'role' => 'customer',
        ]);
        $paidUser->assignRole('customer');

        $basicPlan = Plan::query()->where('slug', 'basic')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $paidUser->id,
            'plan_id' => $basicPlan->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_basic',
            'stripe_status' => 'active',
            'stripe_price' => $basicPlan->stripe_price_id ?: 'price_basic',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $paidResponse = $this->actingAs($paidUser)->get(route('analytics.index'));
        $paidResponse->assertOk();
    }
}
