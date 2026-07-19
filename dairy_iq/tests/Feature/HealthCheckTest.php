<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.ml_service.url' => 'http://ml.test',
        'services.ml_service.timeout' => 3,
    ]);

    Http::preventStrayRequests();
});

test('health check reports ok when database and ml service are available', function () {
    Http::fake([
        'http://ml.test/api/health' => Http::response([
            'status' => 'ok',
            'model_loaded' => true,
            'model_version' => 'milk_quality_rf_v1',
            'feature_count' => 11,
        ]),
    ]);

    $this->get(route('dairyiq.health'))
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'checks' => [
                'laravel' => [
                    'status' => 'ok',
                ],
                'database' => [
                    'status' => 'ok',
                ],
                'ml_service' => [
                    'status' => 'ok',
                    'model_loaded' => true,
                    'model_version' => 'milk_quality_rf_v1',
                    'feature_count' => 11,
                ],
            ],
        ]);
});

test('health check reports degraded when ml service is unavailable', function () {
    Http::fake([
        'http://ml.test/api/health' => Http::response([
            'status' => 'error',
            'model_loaded' => false,
        ], 503),
    ]);

    $this->get(route('dairyiq.health'))
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.ml_service.status', 'error');
});
