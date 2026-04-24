<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProjectPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, config('auth.defaults.guard', 'web'));
        }

        foreach ($this->rolePermissionMap($permissions) as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, config('auth.defaults.guard', 'web'));
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        return [
            'dashboard.view',
            'admin.dashboard.view',
            'customer.dashboard.view',
            'account.profile.view',
            'account.settings.view',
            'account.settings.update',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            'catalog.view',
            'catalog.create',
            'catalog.delete',
            'catalog.download',
            'catalog.manage',
            'catalog.page-management.view',
            'catalog.page-management.update',
            'catalog.page-management.share',
            'catalog.flip-physics.view',
            'catalog.flip-physics.update',
            'catalog.flip-physics.share',
            'catalog.slicer.view',
            'catalog.slicer.update',
            'catalog.slicer.share',
            'catalog.slicer.hotspots.manage',

            'tickets.view',
            'tickets.create',
            'tickets.reply',

            'customer.billing.view',
            'customer.plan.manage',
            'customer.subscription.manage',
            'customer.payment_method.manage',
            'customer.invoice.view',
            'customer.invoice.download',
            'customer.payment.view',
            'customer.analytics.view',
            'notifications.view',
            'notifications.manage',

            'admin.billing.view',
            'admin.plan.manage',
            'admin.subscription.manage',
            'admin.payment.view',
            'admin.payment.refund',
            'admin.invoice.view',
            'admin.invoice.download',
            'admin.webhook.view',
            'admin.notifications.view',
            'admin.notifications.send',
        ];
    }

    /**
     * @param  array<int, string>  $allPermissions
     * @return array<string, array<int, string>>
     */
    private function rolePermissionMap(array $allPermissions): array
    {
        return [
            'admin' => $allPermissions,
            'customer' => [
                'dashboard.view',
                'customer.dashboard.view',
                'account.profile.view',
                'account.settings.view',
                'account.settings.update',

                'catalog.view',
                'catalog.create',
                'catalog.delete',
                'catalog.download',
                'catalog.manage',
                'catalog.page-management.view',
                'catalog.page-management.update',
                'catalog.page-management.share',
                'catalog.flip-physics.view',
                'catalog.flip-physics.update',
                'catalog.flip-physics.share',
                'catalog.slicer.view',
                'catalog.slicer.update',
                'catalog.slicer.share',
                'catalog.slicer.hotspots.manage',

                'tickets.view',
                'tickets.create',
                'tickets.reply',

                'customer.billing.view',
                'customer.plan.manage',
                'customer.subscription.manage',
                'customer.payment_method.manage',
                'customer.invoice.view',
                'customer.invoice.download',
                'customer.payment.view',
                'customer.analytics.view',
                'notifications.view',
                'notifications.manage',
            ],
        ];
    }
}
