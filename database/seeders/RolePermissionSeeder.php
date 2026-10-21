<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Category permissions
            'view categories',
            'create categories',
            'update categories',
            'delete categories',
            // Product permissions
            'view products',
            'create products',
            'update products',
            'delete products',
            // Brand & Unit permissions
            'view brands',
            'create brands',
            'update brands',
            'delete brands',
            'view units',
            'create units',
            'update units',
            'delete units',
            // Store & Inventory permissions
            'view stores',
            'create stores',
            'update stores',
            'view inventory',
            'adjust inventory',
            'transfer inventory',
            // Purchasing permissions
            'view purchases',
            'create purchases',
            'approve purchases',
            // Sales & POS permissions
            'view sales',
            'create sales',
            'cancel sales',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $sales = Role::firstOrCreate([
            'name' => 'sales',
            'guard_name' => 'web',
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin->syncPermissions($permissions);
        $manager->syncPermissions($permissions);

        $sales->syncPermissions([
            'view categories',
            'view products',
            'view brands',
            'view units',
            'view stores',
            'view inventory',
            'view purchases',
            'view sales',
            'create sales',
            'cancel sales',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
