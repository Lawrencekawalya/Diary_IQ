<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;

function legacyImportFile(array $payload): string
{
    $path = tempnam(sys_get_temp_dir(), 'firestore-import-');

    file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR));

    return $path;
}

test('it imports legacy firestore records into a selected company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $path = legacyImportFile([
        'documents' => [
            [
                'name' => 'projects/demo/databases/(default)/documents/milk_batches/legacy-doc-1',
                'fields' => [
                    'Batch Number' => ['stringValue' => 'LEGACY-BATCH-001'],
                    'Collection Center' => ['stringValue' => 'Central Collection'],
                    'District' => ['stringValue' => ' kampala '],
                    'Tested By' => ['stringValue' => 'Legacy Tester'],
                    'Time of Collection' => ['stringValue' => '2026-07-18 14:00:00'],
                    'Number of Liters Collected' => ['doubleValue' => 250.5],
                    'pH' => ['doubleValue' => 6.7],
                    'Temperature' => ['doubleValue' => 4],
                    'Fat_Content' => ['doubleValue' => 3.8],
                    'Titratable_Acidity' => ['doubleValue' => 0.15],
                    'Protein_Content' => ['doubleValue' => 3.3],
                    'Lactose_Content' => ['doubleValue' => 4.8],
                    'TPC' => ['integerValue' => '50000'],
                    'SCC' => ['integerValue' => '200000'],
                    'prediction' => ['stringValue' => 'Moderate'],
                    'confidence' => ['doubleValue' => 0.86],
                    'probabilities' => [
                        'mapValue' => [
                            'fields' => [
                                'Low' => ['doubleValue' => 0.04],
                                'Medium' => ['doubleValue' => 0.86],
                                'High' => ['doubleValue' => 0.10],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $this->artisan('dairyiq:import-firestore', [
        'path' => $path,
        '--company-id' => $company->id,
        '--user-id' => $user->id,
    ])
        ->expectsOutputToContain('Firestore legacy import imported: 1; skipped: 0')
        ->assertSuccessful();

    $batch = MilkBatch::query()->where('batch_number', 'LEGACY-BATCH-001')->firstOrFail();

    expect($batch->company_id)->toBe($company->id)
        ->and($batch->user_id)->toBe($user->id)
        ->and($batch->district)->toBe('Kampala')
        ->and($batch->prediction)->toBe('Medium')
        ->and($batch->ml_prediction)->toBe('Medium')
        ->and($batch->source)->toBe('firestore_legacy')
        ->and($batch->taste)->toBe(1)
        ->and($batch->odor)->toBe(1)
        ->and($batch->color)->toBe(1)
        ->and($batch->sensory_inputs['legacy_missing_sensory_defaults_applied'])->toBeTrue()
        ->and((float) $batch->confidence)->toBe(0.86);
});

test('it supports dry run without creating records', function () {
    $company = Company::factory()->create();
    $path = legacyImportFile([
        [
            'Batch Number' => 'DRY-RUN-001',
            'pH' => 6.7,
            'Temperature' => 4,
            'Taste' => 1,
            'Odor' => 1,
            'Fat_Content' => 3.8,
            'Titratable_Acidity' => 0.15,
            'Protein_Content' => 3.3,
            'Lactose_Content' => 4.8,
            'TPC' => 50000,
            'SCC' => 200000,
            'Color' => 1,
            'prediction' => 'High',
        ],
    ]);

    $this->artisan('dairyiq:import-firestore', [
        'path' => $path,
        '--company-id' => $company->id,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Firestore legacy import validated: 1; skipped: 0')
        ->assertSuccessful();

    expect(MilkBatch::query()->where('batch_number', 'DRY-RUN-001')->exists())->toBeFalse();
});

test('it skips records missing required approved features', function () {
    $company = Company::factory()->create();
    $path = legacyImportFile([
        [
            'Batch Number' => 'INVALID-LEGACY-001',
            'pH' => 6.7,
            'Temperature' => 4,
            'Fat_Content' => 3.8,
            'Titratable_Acidity' => 0.15,
            'Protein_Content' => 3.3,
            'TPC' => 50000,
            'SCC' => 200000,
            'prediction' => 'High',
        ],
    ]);

    $this->artisan('dairyiq:import-firestore', [
        'path' => $path,
        '--company-id' => $company->id,
    ])
        ->expectsOutputToContain('Skipping record 0: missing or invalid Lactose_Content')
        ->expectsOutputToContain('Firestore legacy import imported: 0; skipped: 1')
        ->assertExitCode(2);

    expect(MilkBatch::query()->where('batch_number', 'INVALID-LEGACY-001')->exists())->toBeFalse();
});

test('it skips legacy records with out of range model inputs', function () {
    $company = Company::factory()->create();
    $path = legacyImportFile([
        [
            'Batch Number' => 'OUT-OF-RANGE-LEGACY-001',
            'pH' => 6.7,
            'Temperature' => 4,
            'Taste' => 1,
            'Odor' => 1,
            'Fat_Content' => 5000,
            'Titratable_Acidity' => 0.15,
            'Protein_Content' => 3.3,
            'Lactose_Content' => 4.8,
            'TPC' => 50000,
            'SCC' => 200000,
            'Color' => 1,
            'prediction' => 'High',
        ],
    ]);

    $this->artisan('dairyiq:import-firestore', [
        'path' => $path,
        '--company-id' => $company->id,
    ])
        ->expectsOutputToContain('Skipping record 0: Fat_Content out of supported range 0-20')
        ->expectsOutputToContain('Firestore legacy import imported: 0; skipped: 1')
        ->assertExitCode(2);

    expect(MilkBatch::query()->where('batch_number', 'OUT-OF-RANGE-LEGACY-001')->exists())->toBeFalse();
});
