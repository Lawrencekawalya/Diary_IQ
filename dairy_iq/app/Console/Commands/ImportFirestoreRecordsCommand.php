<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Throwable;

#[Signature('dairyiq:import-firestore
    {path : Path to a JSON export of legacy Firestore milk_batches documents}
    {--company-id= : Company ID to attach imported records to}
    {--user-id= : Optional user ID to attach imported records to}
    {--dry-run : Validate and count records without saving them}')]
#[Description('Import legacy Firestore milk batch records into Laravel SQL storage')]
class ImportFirestoreRecordsCommand extends Command
{
    private const LegacySource = 'firestore_legacy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("Import file not found: {$path}");

            return self::FAILURE;
        }

        try {
            $records = $this->readRecords($path);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $company = $this->resolveCompany();
        $user = $this->resolveUser();
        $dryRun = (bool) $this->option('dry-run');
        $imported = 0;
        $skipped = 0;

        foreach ($records as $index => $record) {
            try {
                $attributes = $this->mapRecord($record, $index, $company, $user);
            } catch (InvalidArgumentException $exception) {
                $skipped++;
                $this->warn("Skipping record {$index}: {$exception->getMessage()}");

                continue;
            }

            if (! $dryRun) {
                MilkBatch::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'batch_number' => $attributes['batch_number'],
                    ],
                    $attributes
                );
            }

            $imported++;
        }

        $action = $dryRun ? 'validated' : 'imported';
        $this->info("Firestore legacy import {$action}: {$imported}; skipped: {$skipped}; company: {$company->id}");

        return $skipped > 0 ? self::INVALID : self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    private function readRecords(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Import file must contain a JSON object or array.');
        }

        $records = $decoded['documents'] ?? $decoded['milk_batches'] ?? $decoded;

        if (! is_array($records)) {
            throw new InvalidArgumentException('Import file must contain an array of records.');
        }

        return collect($records)
            ->map(function (mixed $record, string|int $key): array {
                if (! is_array($record)) {
                    throw new InvalidArgumentException("Record {$key} is not an object.");
                }

                $fields = $record['fields'] ?? $record;

                if (! is_array($fields)) {
                    throw new InvalidArgumentException("Record {$key} does not contain importable fields.");
                }

                $normalized = $this->decodeFirestoreValues($fields);

                if (is_string($key) && ! isset($normalized['document_id'])) {
                    $normalized['document_id'] = $key;
                }

                if (isset($record['name']) && ! isset($normalized['document_id'])) {
                    $normalized['document_id'] = Str::afterLast((string) $record['name'], '/');
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function decodeFirestoreValues(array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (mixed $value, string $key): array => [$key => $this->decodeFirestoreValue($value)])
            ->all();
    }

    private function decodeFirestoreValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (['stringValue', 'integerValue', 'doubleValue', 'booleanValue', 'timestampValue', 'nullValue'] as $type) {
            if (array_key_exists($type, $value)) {
                return match ($type) {
                    'integerValue' => (int) $value[$type],
                    'doubleValue' => (float) $value[$type],
                    'booleanValue' => (bool) $value[$type],
                    'nullValue' => null,
                    default => $value[$type],
                };
            }
        }

        if (array_key_exists('mapValue', $value)) {
            return $this->decodeFirestoreValues($value['mapValue']['fields'] ?? []);
        }

        if (array_key_exists('arrayValue', $value)) {
            return collect($value['arrayValue']['values'] ?? [])
                ->map(fn (mixed $item): mixed => $this->decodeFirestoreValue($item))
                ->all();
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function mapRecord(array $record, int $index, Company $company, ?User $user): array
    {
        $taste = $this->sensoryValue($record, 'Taste', 'taste', 1);
        $odor = $this->sensoryValue($record, 'Odor', 'odor', 1);
        $color = $this->sensoryValue($record, 'Color', 'color', 1);
        $prediction = $this->qualityLabel($this->first($record, ['prediction', 'Prediction', 'quality']));

        if ($prediction === null) {
            throw new InvalidArgumentException('missing or invalid prediction');
        }

        return [
            'company_id' => $company->id,
            'user_id' => $user?->id,
            'batch_number' => $this->batchNumber($record, $index),
            'collection_center' => $this->nullableString($this->first($record, ['Collection Center', 'collection_center'])),
            'district' => $this->nullableString($this->first($record, ['District', 'district'])),
            'tested_by' => $this->nullableString($this->first($record, ['Tested By', 'tested_by'])),
            'collected_at' => $this->timestamp($this->first($record, ['Time of Collection', 'collected_at', 'created_at'])),
            'liters_collected' => $this->nullableFloat($this->first($record, ['Number of Liters Collected', 'liters_collected'])),
            'ph' => $this->requiredFloat($record, ['pH', 'ph'], 'pH'),
            'temperature' => $this->requiredFloat($record, ['Temperature', 'temperature'], 'Temperature'),
            'taste' => $taste,
            'odor' => $odor,
            'fat_content' => $this->requiredFloat($record, ['Fat_Content', 'fat_content'], 'Fat_Content'),
            'titratable_acidity' => $this->requiredFloat($record, ['Titratable_Acidity', 'titratable_acidity'], 'Titratable_Acidity'),
            'protein_content' => $this->requiredFloat($record, ['Protein_Content', 'protein_content'], 'Protein_Content'),
            'lactose_content' => $this->requiredFloat($record, ['Lactose_Content', 'lactose_content'], 'Lactose_Content'),
            'tpc' => $this->requiredInteger($record, ['TPC', 'tpc'], 'TPC'),
            'scc' => $this->requiredInteger($record, ['SCC', 'scc'], 'SCC'),
            'color' => $color,
            'sensory_inputs' => $this->sensoryInputs($record, $taste, $odor, $color),
            'encoded_sensory_values' => [
                'Taste' => $taste,
                'Odor' => $odor,
                'Color' => $color,
            ],
            'prediction' => $prediction,
            'ml_prediction' => $this->qualityLabel($this->first($record, ['ml_prediction', 'ML Prediction'])) ?? $prediction,
            'confidence' => $this->nullableFloat($this->first($record, ['confidence', 'Confidence'])),
            'probabilities' => $this->arrayOrNull($this->first($record, ['probabilities', 'Probabilities'])),
            'standards_observations' => $this->arrayOrNull($this->first($record, ['standards_observations', 'Standards Observations'])) ?? [],
            'standards_quality_gate' => $this->arrayOrNull($this->first($record, ['standards_quality_gate'])) ?? [],
            'feature_status' => $this->arrayOrNull($this->first($record, ['feature_status', 'colors'])) ?? [],
            'model_metadata' => $this->arrayOrNull($this->first($record, ['model_metadata'])) ?? [],
            'record_schema_version' => $this->nullableString($this->first($record, ['record_schema_version'])) ?? 'milk_batch_prediction_v1',
            'source' => self::LegacySource,
        ];
    }

    private function resolveCompany(): Company
    {
        $companyId = $this->option('company-id');

        if ($companyId !== null) {
            return Company::query()->findOrFail((int) $companyId);
        }

        return Company::query()->firstOrCreate(
            ['name' => 'Legacy Firestore Imports'],
            ['contact_email' => null, 'phone' => null, 'address' => null]
        );
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user-id');

        if ($userId === null) {
            return null;
        }

        return User::query()->findOrFail((int) $userId);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    private function first(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($record, $key)) {
                return Arr::get($record, $key);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function batchNumber(array $record, int $index): string
    {
        $batchNumber = $this->nullableString($this->first($record, ['Batch Number', 'batch_number']));

        if ($batchNumber !== null) {
            return $batchNumber;
        }

        $documentId = $this->nullableString($record['document_id'] ?? null);

        return 'LEGACY-'.($documentId ?: Str::padLeft((string) ($index + 1), 5, '0'));
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    private function requiredFloat(array $record, array $keys, string $field): float
    {
        $value = $this->first($record, $keys);

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("missing or invalid {$field}");
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    private function requiredInteger(array $record, array $keys, string $field): int
    {
        $value = $this->first($record, $keys);

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("missing or invalid {$field}");
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function qualityLabel(mixed $value): ?string
    {
        return match ($this->nullableString($value)) {
            'Low' => 'Low',
            'Moderate', 'Medium' => 'Medium',
            'High' => 'High',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function sensoryValue(array $record, string $modelKey, string $inputKey, int $default): int
    {
        $encoded = $this->arrayOrNull($record['encoded_sensory_values'] ?? null);
        $value = $encoded[$modelKey] ?? $this->first($record, [$modelKey, $inputKey]);

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return (int) $value === 1 ? 1 : 0;
        }

        return in_array(Str::lower((string) $value), ['normal', 'acceptable', 'fresh', 'creamy-white', 'white'], true) ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function sensoryInputs(array $record, int $taste, int $odor, int $color): array
    {
        $sensoryInputs = $this->arrayOrNull($record['sensory_inputs'] ?? null) ?? [];

        return [
            'taste' => $sensoryInputs['taste'] ?? $sensoryInputs['Taste'] ?? ($taste === 1 ? 'normal' : 'abnormal'),
            'odor' => $sensoryInputs['odor'] ?? $sensoryInputs['Odor'] ?? ($odor === 1 ? 'fresh' : 'abnormal'),
            'color' => $sensoryInputs['color'] ?? $sensoryInputs['Color'] ?? ($color === 1 ? 'normal' : 'abnormal'),
            'legacy_missing_sensory_defaults_applied' => ! isset($record['Taste'], $record['taste'], $record['Odor'], $record['odor'], $record['Color'], $record['color'], $record['sensory_inputs'], $record['encoded_sensory_values']),
        ];
    }

    /**
     * @return array<mixed>|null
     */
    private function arrayOrNull(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_array($value) && isset($value['_seconds'])) {
            return CarbonImmutable::createFromTimestamp((int) $value['_seconds']);
        }

        if (is_string($value) && $value !== '') {
            try {
                return CarbonImmutable::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
