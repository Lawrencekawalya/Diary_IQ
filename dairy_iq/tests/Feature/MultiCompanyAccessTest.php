<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('super admin sees platform dashboard', function () {
    $company = Company::factory()->create(['name' => 'Kawaly Dairy']);
    User::factory()->for($company)->companyAdmin()->create();
    MilkBatch::factory()->for($company)->create([
        'prediction' => 'High',
        'liters_collected' => 200,
    ]);
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('summary.companies', 1)
            ->where('summary.batches', 1)
            ->where('summary.High', 1)
            ->where('companyPerformance.0.name', 'Kawaly Dairy')
            ->where('recentBatches.0.company', 'Kawaly Dairy')
        );
});

test('company users keep the company analytics dashboard', function () {
    $company = Company::factory()->create();
    $companyAdmin = User::factory()->for($company)->companyAdmin()->create();

    $this->actingAs($companyAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
        );
});

test('super admin can view company administration workspace', function () {
    Company::factory()->create(['name' => 'Kawaly Dairy']);
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Companies')
            ->where('companies.data.0.name', 'Kawaly Dairy')
            ->where('roles', ['company_admin', 'tester'])
            ->where('platformRoles', ['super_admin', 'company_admin', 'tester'])
        );
});

test('super admin can view administration documentation', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.documentation.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Documentation')
        );
});

test('company users cannot access super admin company administration', function () {
    $company = Company::factory()->create();
    $companyAdmin = User::factory()->for($company)->companyAdmin()->create();

    $this->actingAs($companyAdmin)
        ->get(route('admin.companies.index'))
        ->assertForbidden();

    $this->actingAs($companyAdmin)
        ->get(route('admin.documentation.index'))
        ->assertForbidden();
});

test('super admin can create a company with its first company admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.companies.store'), [
            'name' => 'Bobdee Dairy',
            'contact_email' => 'info@bobdee.test',
            'phone' => '+256700000000',
            'address' => 'Kampala',
            'admin_name' => 'Bobdee Admin',
            'admin_email' => 'admin@bobdee.test',
            'admin_password' => 'password123',
        ])
        ->assertRedirect();

    $company = Company::where('name', 'Bobdee Dairy')->firstOrFail();
    $admin = User::where('email', 'admin@bobdee.test')->firstOrFail();

    expect($admin->company_id)->toBe($company->id)
        ->and($admin->role)->toBe('company_admin');
});

test('super admin can create another super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.users.store'), [
            'name' => 'Second Super Admin',
            'email' => 'second-super@example.test',
            'password' => 'password123',
            'role' => 'super_admin',
        ])
        ->assertRedirect();

    $newSuperAdmin = User::where('email', 'second-super@example.test')->firstOrFail();

    expect($newSuperAdmin->company_id)->toBeNull()
        ->and($newSuperAdmin->role)->toBe('super_admin');
});

test('super admin can update users roles and reset temporary passwords', function () {
    $company = Company::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $user = User::factory()->for($company)->tester()->create([
        'email' => 'editable@example.test',
    ]);

    $this->actingAs($superAdmin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Edited Admin',
            'email' => 'edited@example.test',
            'role' => 'company_admin',
            'company_id' => $company->id,
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Edited Admin')
        ->and($user->email)->toBe('edited@example.test')
        ->and($user->role)->toBe('company_admin')
        ->and($user->company_id)->toBe($company->id);

    $this->actingAs($superAdmin)
        ->put(route('admin.users.password', $user), [
            'password' => 'temporary123',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->force_password_change)->toBeTrue();
});

test('super admin can deactivate activate and safely delete users', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $user = User::factory()->tester()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.users.deactivate', $user))
        ->assertRedirect();

    expect($user->refresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)
        ->put(route('admin.users.activate', $user))
        ->assertRedirect();

    expect($user->refresh()->is_active)->toBeTrue();

    $this->actingAs($superAdmin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    expect(User::whereKey($user->id)->exists())->toBeFalse();
});

test('super admin can update archive and restore companies', function () {
    $company = Company::factory()->create(['name' => 'Old Name']);
    $user = User::factory()->for($company)->tester()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.companies.update', $company), [
            'name' => 'Updated Dairy',
            'contact_email' => 'updated@example.test',
            'phone' => '123',
            'address' => 'Kampala',
        ])
        ->assertRedirect();

    expect($company->refresh()->name)->toBe('Updated Dairy');

    $this->actingAs($superAdmin)
        ->put(route('admin.companies.archive', $company))
        ->assertRedirect();

    expect($company->refresh()->archived_at)->not->toBeNull()
        ->and($user->refresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)
        ->put(route('admin.companies.restore', $company))
        ->assertRedirect();

    expect($company->refresh()->archived_at)->toBeNull();
});

test('super admin can view a selected company dashboard', function () {
    $company = Company::factory()->create(['name' => 'Dashboard Dairy']);
    MilkBatch::factory()->for($company)->create(['prediction' => 'Medium']);
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.companies.dashboard', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('adminViewingCompany.name', 'Dashboard Dairy')
            ->where('summary.Medium', 1)
        );
});

test('forced password users must change password before continuing', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('password.force.edit'));

    $this->actingAs($user)
        ->put(route('password.force.update'), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->force_password_change)->toBeFalse();
});

test('super admin must choose a company when creating company-scoped users', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.users.store'), [
            'name' => 'Company Tester',
            'email' => 'company-tester@example.test',
            'password' => 'password123',
            'role' => 'tester',
        ])
        ->assertSessionHasErrors('company_id');
});

test('company admin can create users only inside their company', function () {
    $company = Company::factory()->create();
    $companyAdmin = User::factory()->for($company)->companyAdmin()->create();

    $this->actingAs($companyAdmin)
        ->post(route('company.users.store'), [
            'name' => 'Quality Tester',
            'email' => 'tester@company.test',
            'password' => 'password123',
            'role' => 'tester',
        ])
        ->assertRedirect();

    $tester = User::where('email', 'tester@company.test')->firstOrFail();

    expect($tester->company_id)->toBe($company->id)
        ->and($tester->role)->toBe('tester');
});

test('tester cannot manage company users or settings', function () {
    $company = Company::factory()->create();
    $tester = User::factory()->for($company)->tester()->create();

    $this->actingAs($tester)
        ->get(route('company.users.index'))
        ->assertForbidden();

    $this->actingAs($tester)
        ->get(route('company.settings.edit'))
        ->assertForbidden();

    $this->actingAs($tester)
        ->put(route('company.settings.update'), [
            'name' => 'Changed Name',
            'contact_email' => 'changed@example.test',
            'phone' => null,
            'address' => null,
        ])
        ->assertForbidden();
});
