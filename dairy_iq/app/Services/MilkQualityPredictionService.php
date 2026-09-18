<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MilkQualityPredictionService
{
    public function predict(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.ml_service.url'), '/');
        $token = config('services.ml_service.token');

        if (! $token) {
            throw new RuntimeException('ML service token is not configured.');
        }

        try {
            $response = Http::timeout((int) config('services.ml_service.timeout', 10))
                ->acceptJson()
                ->withToken($token)
                ->post("{$baseUrl}/api/predict", $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to connect to the ML prediction service.', previous: $exception);
        }

        if ($response->failed()) {
            $message = $response->json('error') ?: 'The ML prediction service could not process the request.';

            throw new RuntimeException($message);
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException('The ML prediction service returned an invalid response.');
        }

        foreach ($this->requiredResponseKeys() as $key) {
            if (! array_key_exists($key, $result)) {
                throw new RuntimeException("The ML prediction response is missing {$key}.");
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function requiredResponseKeys(): array
    {
        return [
            'prediction',
            'ml_prediction',
            'confidence',
            'probabilities',
            'standards_observations',
            'standards_quality_gate',
            'model_metadata',
            'raw',
            'sensory_inputs',
            'encoded_sensory_values',
        ];
    }
}
