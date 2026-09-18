<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a company owns users and milk batch records', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $batch = MilkBatch::factory()
        ->for($company)
        ->for($user)
        ->create([
            'batch_number' => 'BATCH-HIGH-001',
            'prediction' => 'High',
            'ml_prediction' => 'High',
        ]);

    expect($company->users()->pluck('id'))->toContain($user->id)
        ->and($company->milkBatches()->pluck('id'))->toContain($batch->id)
        ->and($user->company->is($company))->toBeTrue()
        ->and($user->milkBatches()->pluck('id'))->toContain($batch->id)
        ->and($batch->company->is($company))->toBeTrue()
        ->and($batch->user->is($user))->toBeTrue();
});

test('milk batch records persist the approved eleven feature contract and prediction metadata', function () {
    $batch = MilkBatch::factory()->create([
        'ph' => 6.65,
        'temperature' => 8.50,
        'taste' => 1,
        'odor' => 1,
        'fat_content' => 3.20,
        'titratable_acidity' => 0.180,
        'protein_content' => 3.20,
        'lactose_content' => 4.60,
        'tpc' => 1800000,
        'scc' => 400000,
        'color' => 1,
        'prediction' => 'Medium',
        'ml_prediction' => 'Medium',
        'confidence' => 0.8667,
        'probabilities' => [
            'Low' => 0.0000,
            'Medium' => 0.8667,
            'High' => 0.1333,
        ],
        'standards_observations' => [
            'Warning: Temperature above normal.',
        ],
        'standards_quality_gate' => [
            'passed' => false,
            'downgraded' => false,
        ],
        'feature_status' => [
            'pH' => true,
            'Temperature' => false,
            'Taste' => true,
            'Odor' => true,
            'Fat_Content' => false,
            'Titratable_Acidity' => false,
            'Protein_Content' => true,
            'Lactose_Content' => true,
            'TPC' => true,
            'SCC' => false,
            'Color' => true,
        ],
    ])->refresh();

    expect($batch->ph)->toBe('6.65')
        ->and($batch->temperature)->toBe('8.50')
        ->and($batch->taste)->toBe(1)
        ->and($batch->odor)->toBe(1)
        ->and($batch->fat_content)->toBe('3.20')
        ->and($batch->titratable_acidity)->toBe('0.180')
        ->and($batch->protein_content)->toBe('3.20')
        ->and($batch->lactose_content)->toBe('4.60')
        ->and($batch->tpc)->toBe(1800000)
        ->and($batch->scc)->toBe(400000)
        ->and($batch->color)->toBe(1)
        ->and($batch->prediction)->toBe('Medium')
        ->and($batch->ml_prediction)->toBe('Medium')
        ->and($batch->confidence)->toBe('0.8667')
        ->and($batch->probabilities['Medium'])->toBe(0.8667)
        ->and($batch->standards_observations)->toContain('Warning: Temperature above normal.')
        ->and($batch->standards_quality_gate['passed'])->toBeFalse()
        ->and($batch->feature_status['Temperature'])->toBeFalse()
        ->and($batch->model_metadata['model_type'])->toBe('RandomForestClassifier');
});

test('milk batch query scope keeps company records isolated', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $visibleBatch = MilkBatch::factory()->for($company)->create([
        'batch_number' => 'VISIBLE-001',
    ]);

    MilkBatch::factory()->for($otherCompany)->create([
        'batch_number' => 'HIDDEN-001',
    ]);

    expect(MilkBatch::forCompany($company->id)->pluck('id')->all())
        ->toBe([$visibleBatch->id]);
});

test('approved feature contract reference page shows shared standards', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)
        ->get(route('approved-features.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reference/ApprovedFeatures')
            ->has('features', 11)
            ->where('features.0.Parameter', 'pH')
            ->where('features.0.Source', 'US EAS 67:2023')
            ->where('features.10.Parameter', 'Color')
        );
});
