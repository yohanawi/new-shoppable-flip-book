<?php

namespace Tests\Feature\UserManagement;

use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_does_not_render_user_management_menu(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $response = $this->actingAs($customer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('user-management.users.index'), false);
        $response->assertDontSee(route('user-management.roles.index'), false);
        $response->assertDontSee(route('user-management.permissions.index'), false);
    }

    public function test_customer_cannot_access_user_management_routes(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('user-management.users.index'))
            ->assertForbidden();
    }
}
