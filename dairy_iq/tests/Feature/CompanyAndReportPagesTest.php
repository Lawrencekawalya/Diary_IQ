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

test('report preview is generated from a saved company record', function () {
    $company = Company::factory()->create([
        'name' => 'DairyIQ Report Company',
        'contact_email' => 'reports@example.com',
    ]);
    $user = User::factory()->for($company)->create([
        'name' => 'Quality Reporter',
    ]);
    $batch = MilkBatch::factory()->for($company)->for($user)->create([
        'batch_number' => 'REPORT-EXPORT-001',
        'tested_by' => 'Senior Tester',
        'collection_center' => 'Central Collection',
        'prediction' => 'High',
        'ml_prediction' => 'High',
        'model_metadata' => [
            'model_version' => 'milk_quality_rf_v1',
            'dataset_version' => 'milk_quality_eas67_2023_synth_1500_v1',
            'label_policy_version' => 'approved_thesis_3class_v1',
            'classes' => ['Low', 'Medium', 'High'],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('reports.preview', $batch))
        ->assertOk()
        ->assertSee('DairyIQ Analytics')
        ->assertSee('Download PDF')
        ->assertDontSee('Export / Save as PDF')
        ->assertSee('Executive Summary')
        ->assertSee('Measured Inputs and Standards Status')
        ->assertSee('Class Probabilities')
        ->assertSee('Model Metadata')
        ->assertSee('DairyIQ Report Company')
        ->assertSee('Senior Tester')
        ->assertSee('REPORT-EXPORT-001')
        ->assertSee('milk_quality_rf_v1');
});

test('report pdf is downloaded from a saved company record', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $batch = MilkBatch::factory()->for($company)->for($user)->create([
        'batch_number' => 'REPORT-PDF-001',
    ]);

    $response = $this->actingAs($user)
        ->get(route('reports.show', $batch))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename=DairyIQ_Report_REPORT-PDF-001.pdf');

    expect(preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()))->toBeLessThanOrEqual(3);
});

test('report routes reject another company record', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $otherBatch = MilkBatch::factory()->for($otherCompany)->create();

    $this->actingAs($user)
        ->get(route('reports.show', $otherBatch))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('reports.preview', $otherBatch))
        ->assertNotFound();
});
