<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'group' => 'roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'roles'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions'],
            ['name' => 'Create Permissions', 'slug' => 'permissions.create', 'group' => 'permissions'],
            ['name' => 'Edit Permissions', 'slug' => 'permissions.edit', 'group' => 'permissions'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'group' => 'permissions'],
            ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings'],
            ['name' => 'Edit Settings', 'slug' => 'settings.edit', 'group' => 'settings'],
            ['name' => 'View Plans', 'slug' => 'plans.view', 'group' => 'plans'],
            ['name' => 'Create Plans', 'slug' => 'plans.create', 'group' => 'plans'],
            ['name' => 'Edit Plans', 'slug' => 'plans.edit', 'group' => 'plans'],
            ['name' => 'Delete Plans', 'slug' => 'plans.delete', 'group' => 'plans'],
            ['name' => 'View Brands', 'slug' => 'brands.view', 'group' => 'brands'],
            ['name' => 'Delete Brands', 'slug' => 'brands.delete', 'group' => 'brands'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $allPermissionIds = Permission::query()->pluck('id');

        $superAdmin = Role::query()->updateOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'description' => 'Full platform access', 'is_active' => true]
        );
        $superAdmin->permissions()->sync($allPermissionIds);

        $support = Role::query()->updateOrCreate(
            ['slug' => 'support'],
            ['name' => 'Support', 'description' => 'Customer support team', 'is_active' => true]
        );
        $support->permissions()->sync(
            Permission::query()->whereIn('slug', [
                'dashboard.view', 'users.view', 'users.edit', 'settings.view', 'plans.view', 'brands.view',
            ])->pluck('id')
        );

        $analyst = Role::query()->updateOrCreate(
            ['slug' => 'analyst'],
            ['name' => 'Analyst', 'description' => 'Read-only analytics access', 'is_active' => true]
        );
        $analyst->permissions()->sync(
            Permission::query()->whereIn('slug', ['dashboard.view', 'users.view'])->pluck('id')
        );
    }
}
