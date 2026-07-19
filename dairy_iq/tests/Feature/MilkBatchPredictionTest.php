<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'services.ml_service.url' => 'http://ml.test',
        'services.ml_service.token' => 'test-secret',
        'services.ml_service.timeout' => 3,
    ]);

    Http::preventStrayRequests();
});

function predictionPayload(): array
{
    return [
        'batch_number' => 'BATCH-HIGH-001',
        'collection_center' => 'Main Collection Center',
        'district' => 'Kampala',
        'tested_by' => 'Quality Officer',
        'liters_collected' => 120.50,
        'pH' => 6.70,
        'Temperature' => 4.0,
        'Taste' => 'normal',
        'Odor' => 'fresh',
        'Fat_Content' => 3.8,
        'Titratable_Acidity' => 0.15,
        'Protein_Content' => 3.3,
        'Lactose_Content' => 4.8,
        'TPC' => 50000,
        'SCC' => 200000,
        'Color' => 'normal',
    ];
}

function mlPredictionResponse(array $overrides = []): array
{
    return [
        ...[
            'prediction' => 'High',
            'ml_prediction' => 'High',
            'confidence' => 0.9333,
            'probabilities' => [
                'Low' => 0.0,
                'Medium' => 0.0667,
                'High' => 0.9333,
            ],
            'standards_observations' => [
                'Milk meets the configured standards thresholds.',
                'Maintain current handling procedures.',
            ],
            'standards_quality_gate' => [
                'applied' => false,
                'ml_prediction' => 'High',
                'final_prediction' => 'High',
                'max_allowed_quality' => 'High',
                'failed_features' => [],
                'critical_features' => [],
                'reason' => 'No standards gate downgrade was required.',
            ],
            'model_metadata' => [
                'model_version' => 'milk_quality_rf_v1',
                'dataset_version' => 'milk_quality_eas67_2023_synth_1500_v1',
                'label_policy_version' => 'approved_thesis_3class_v1',
                'feature_order' => [
                    'pH',
                    'Temperature',
                    'Taste',
                    'Odor',
                    'Fat_Content',
                    'Titratable_Acidity',
                    'Protein_Content',
                    'Lactose_Content',
                    'TPC',
                    'SCC',
                    'Color',
                ],
                'classes' => ['Low', 'Medium', 'High'],
            ],
            'feature_status' => [
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
                '#2ecc71',
            ],
            'raw' => [
                'pH' => 6.7,
                'Temperature' => 4.0,
                'Taste' => 1,
                'Odor' => 1,
                'Fat_Content' => 3.8,
                'Titratable_Acidity' => 0.15,
                'Protein_Content' => 3.3,
                'Lactose_Content' => 4.8,
                'TPC' => 50000,
                'SCC' => 200000,
                'Color' => 1,
            ],
            'sensory_inputs' => [
                'Taste' => 'normal',
                'Odor' => 'fresh',
                'Color' => 'normal',
            ],
            'encoded_sensory_values' => [
                'Taste' => 1,
                'Odor' => 1,
                'Color' => 1,
            ],
        ],
        ...$overrides,
    ];
}

test('authenticated company user can request prediction and store milk batch record', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    Http::fake([
        'http://ml.test/api/predict' => Http::response(mlPredictionResponse()),
    ]);

    $response = $this->actingAs($user)->postJson(
        route('milk-batches.predictions.store'),
        predictionPayload()
    );

    $response
        ->assertCreated()
        ->assertJson([
            'batch_number' => 'BATCH-HIGH-001',
            'prediction' => 'High',
            'ml_prediction' => 'High',
            'confidence' => '0.9333',
        ]);

    $batch = MilkBatch::firstOrFail();

    expect($batch->company_id)->toBe($company->id)
        ->and($batch->user_id)->toBe($user->id)
        ->and($batch->prediction)->toBe('High')
        ->and($batch->ml_prediction)->toBe('High')
        ->and($batch->taste)->toBe(1)
        ->and($batch->odor)->toBe(1)
        ->and($batch->color)->toBe(1)
        ->and($batch->sensory_inputs)->toBe([
            'Taste' => 'normal',
            'Odor' => 'fresh',
            'Color' => 'normal',
        ])
        ->and($batch->encoded_sensory_values)->toBe([
            'Taste' => 1,
            'Odor' => 1,
            'Color' => 1,
        ])
        ->and($batch->probabilities['High'])->toBe(0.9333)
        ->and($batch->standards_quality_gate['applied'])->toBeFalse()
        ->and($batch->model_metadata['model_version'])->toBe('milk_quality_rf_v1');

    Http::assertSent(function ($request) {
        return $request->url() === 'http://ml.test/api/predict'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-secret')
            && $request['pH'] == 6.70
            && $request['Temperature'] == 4.0
            && $request['Taste'] === 'normal'
            && $request['Odor'] === 'fresh'
            && $request['Fat_Content'] == 3.8
            && $request['Titratable_Acidity'] == 0.15
            && $request['Protein_Content'] == 3.3
            && $request['Lactose_Content'] == 4.8
            && $request['TPC'] == 50000
            && $request['SCC'] == 200000
            && $request['Color'] === 'normal'
            && ! array_key_exists('SNF', $request->data())
            && ! array_key_exists('Turbidity', $request->data());
    });
});

test('prediction form page is displayed to authenticated company users', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)
        ->get(route('milk-batches.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('milk-batches/Create')
        );
});

test('browser prediction submit redirects to saved result page', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    Http::fake([
        'http://ml.test/api/predict' => Http::response(mlPredictionResponse()),
    ]);

    $response = $this->actingAs($user)
        ->post(route('milk-batches.predictions.store'), predictionPayload());

    $batch = MilkBatch::firstOrFail();

    $response->assertRedirect(route('milk-batches.show', $batch));
});

test('history page is paginated by latest company records only', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    MilkBatch::factory()->count(25)->sequence(fn ($sequence) => [
        'company_id' => $company->id,
        'batch_number' => 'VISIBLE-'.$sequence->index,
        'created_at' => now()->subMinutes($sequence->index),
    ])->create();
    MilkBatch::factory()->for($otherCompany)->create([
        'batch_number' => 'HIDDEN-001',
    ]);

    $this->actingAs($user)
        ->get(route('milk-batches.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('milk-batches/History')
            ->where('batches.per_page', 20)
            ->where('batches.total', 25)
            ->where('batches.data.0.batch_number', 'VISIBLE-0')
            ->where('insights.total', 25)
        );
});

test('result page rejects another company record', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $otherBatch = MilkBatch::factory()->for($otherCompany)->create();

    $this->actingAs($user)
        ->get(route('milk-batches.show', $otherBatch))
        ->assertNotFound();
});

test('laravel validation rejects invalid input before calling ml service', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $payload = predictionPayload();
    unset($payload['pH']);

    Http::fake();

    $this->actingAs($user)
        ->postJson(route('milk-batches.predictions.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pH']);

    Http::assertNothingSent();
});

test('existing users without a company get a company before creating prediction records', function () {
    $user = User::factory()->create(['company_id' => null]);

    Http::fake([
        'http://ml.test/api/predict' => Http::response(mlPredictionResponse()),
    ]);

    $this->actingAs($user)
        ->postJson(route('milk-batches.predictions.store'), predictionPayload())
        ->assertCreated();

    expect($user->refresh()->company_id)->not->toBeNull()
        ->and(MilkBatch::firstOrFail()->company_id)->toBe($user->company_id);
});

test('ml service errors are returned safely and do not create records', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    Http::fake([
        'http://ml.test/api/predict' => Http::response([
            'error' => 'Model artifact is unavailable.',
        ], 503),
    ]);

    $this->actingAs($user)
        ->postJson(route('milk-batches.predictions.store'), predictionPayload())
        ->assertStatus(502)
        ->assertJson([
            'message' => 'Prediction could not be completed.',
            'error' => 'Model artifact is unavailable.',
        ]);

    expect(MilkBatch::count())->toBe(0);
});
