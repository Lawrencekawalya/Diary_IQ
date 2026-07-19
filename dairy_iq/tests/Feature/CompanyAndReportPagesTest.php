<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('company settings page displays and updates company profile', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)
        ->get(route('company.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('company/Settings')
            ->where('company.name', $company->name)
        );

    $this->actingAs($user)
        ->put(route('company.settings.update'), [
            'name' => 'DairyIQ Test Company',
            'contact_email' => 'quality@example.com',
            'phone' => '0700000000',
            'address' => 'Kampala',
        ])
        ->assertRedirect();

    expect($company->refresh()->name)->toBe('DairyIQ Test Company')
        ->and($company->contact_email)->toBe('quality@example.com');
});

test('reports page lists company scoped saved records', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    MilkBatch::factory()->for($company)->create([
        'batch_number' => 'REPORT-VISIBLE',
    ]);
    MilkBatch::factory()->for($otherCompany)->create([
        'batch_number' => 'REPORT-HIDDEN',
    ]);

    $this->actingAs($user)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->has('batches', 1)
            ->where('batches.0.batch_number', 'REPORT-VISIBLE')
        );
});
