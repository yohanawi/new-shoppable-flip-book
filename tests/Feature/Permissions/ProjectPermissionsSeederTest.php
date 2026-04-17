<?php

namespace Tests\Feature\Permissions;

use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_permissions_seeder_creates_expected_roles_and_permissions(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'customer',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'catalog.slicer.hotspots.manage',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'admin.webhook.view',
        ]);

        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $customerRole = Role::query()->where('name', 'customer')->firstOrFail();

        $this->assertTrue($adminRole->hasPermissionTo('users.delete'));
        $this->assertTrue($adminRole->hasPermissionTo('admin.plan.manage'));
        $this->assertTrue($customerRole->hasPermissionTo('catalog.create'));
        $this->assertTrue($customerRole->hasPermissionTo('customer.billing.view'));
        $this->assertFalse($customerRole->hasPermissionTo('users.delete'));
        $this->assertFalse($customerRole->hasPermissionTo('admin.plan.manage'));

        $this->assertGreaterThanOrEqual(1, Permission::query()->count());
    }
}
