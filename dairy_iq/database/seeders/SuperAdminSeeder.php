<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the initial platform super-admin account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'company_id' => null,
                'role' => 'super_admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
            ],
        );
    }
}
