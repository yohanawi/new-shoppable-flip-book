<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Plan setup required');
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
