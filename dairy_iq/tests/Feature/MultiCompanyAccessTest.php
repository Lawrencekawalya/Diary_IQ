<?php

use App\Models\Company;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('super admin can view company administration workspace', function () {
    Company::factory()->create(['name' => 'Kawaly Dairy']);
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Companies')
            ->where('companies.0.name', 'Kawaly Dairy')
            ->where('roles', ['company_admin', 'tester'])
        );
});

test('company users cannot access super admin company administration', function () {
    $company = Company::factory()->create();
    $companyAdmin = User::factory()->for($company)->companyAdmin()->create();

    $this->actingAs($companyAdmin)
        ->get(route('admin.companies.index'))
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
