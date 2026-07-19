<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'laravel' => [
                'status' => 'ok',
            ],
            'database' => $this->databaseCheck(),
            'ml_service' => $this->mlServiceCheck(),
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed.',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mlServiceCheck(): array
    {
        $baseUrl = rtrim((string) config('services.ml_service.url'), '/');

        if ($baseUrl === '') {
            return [
                'status' => 'error',
                'message' => 'ML service URL is not configured.',
            ];
        }

        try {
            $response = Http::timeout((int) config('services.ml_service.timeout', 10))
                ->get("{$baseUrl}/api/health");
        } catch (ConnectionException) {
            return [
                'status' => 'error',
                'message' => 'ML service is unreachable.',
            ];
        }

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => 'ML service health endpoint returned an error.',
            ];
        }

        $payload = $response->json();

        return [
            'status' => ($payload['status'] ?? null) === 'ok' && ($payload['model_loaded'] ?? false) === true
                ? 'ok'
                : 'error',
            'model_loaded' => (bool) ($payload['model_loaded'] ?? false),
            'model_version' => $payload['model_version'] ?? null,
            'feature_count' => $payload['feature_count'] ?? null,
        ];
    }
}
