<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::query()->updateOrCreate(
            ['email' => 'admin@cmoai.app'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::query()->where('slug', 'super_admin')->first();

        if ($superAdminRole) {
            $admin->roles()->sync([$superAdminRole->id]);
        }
    }
}
