<?php

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;

test('super admin seeder creates the platform admin account', function () {
    $this->seed(SuperAdminSeeder::class);

    $admin = User::where('email', 'superadmin@example.com')->firstOrFail();

    expect($admin->name)->toBe('Super Admin')
        ->and($admin->company_id)->toBeNull()
        ->and($admin->role)->toBe('super_admin')
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});
