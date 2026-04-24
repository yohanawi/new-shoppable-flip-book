<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_admin_view_model(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardRole', 'admin');
        $response->assertViewHas('cards');
        $response->assertViewHas('catalogTable');
    }

    public function test_customer_dashboard_uses_customer_view_model(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $response = $this->actingAs($customer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardRole', 'customer');
        $response->assertViewHas('cards');
        $response->assertViewHas('secondaryPanel');
    }

    public function test_authenticated_user_without_dashboard_role_permissions_is_forbidden(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
