<?php

namespace App\Http\Requests;

use App\Models\CollectionCenter;
use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreMilkBatchPredictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->company_id !== null
            && ! $this->user()->isSuperAdmin();
    }

    protected function prepareForValidation(): void
    {
        $companyId = (int) $this->user()?->company_id;

        if ($companyId) {
            if ($this->has('collection_center_id') && $this->input('collection_center_id')) {
                $center = CollectionCenter::query()
                    ->where('company_id', $companyId)
                    ->find($this->input('collection_center_id'));
                if ($center) {
                    $this->merge([
                        'collection_center' => $center->name,
                        'district' => $this->input('district') ?: $center->district,
                    ]);
                }
            } elseif (! $this->has('collection_center_id') && $this->filled('collection_center')) {
                $center = CollectionCenter::query()
                    ->where('company_id', $companyId)
                    ->where('name', trim((string) $this->input('collection_center')))
                    ->first();
                if ($center) {
                    $this->merge(['collection_center_id' => $center->id]);
                }
            }

            if ($this->has('vehicle_id') && $this->input('vehicle_id')) {
                $vehicle = Vehicle::query()
                    ->where('company_id', $companyId)
                    ->find($this->input('vehicle_id'));
                if ($vehicle) {
                    $this->merge([
                        'vehicle_number' => $vehicle->plate_number,
                        'driver_name' => $this->input('driver_name') ?: $vehicle->driver_name,
                    ]);
                }
            } elseif (! $this->has('vehicle_id') && $this->filled('vehicle_number')) {
                $vehicle = Vehicle::query()
                    ->where('company_id', $companyId)
                    ->where('plate_number', strtoupper(trim((string) $this->input('vehicle_number'))))
                    ->first();
                if ($vehicle) {
                    $this->merge(['vehicle_id' => $vehicle->id]);
                }
            }
        }

        if ($this->has('district')) {
            $this->merge([
                'district' => $this->normalizeDistrict($this->input('district')),
            ]);
        }

        if ($this->has('driver_name') || $this->has('vehicle_number')) {
            $this->merge([
                'driver_name' => $this->normalizeText($this->input('driver_name')),
                'vehicle_number' => $this->normalizeVehicleNumber($this->input('vehicle_number')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()?->company_id;

        return [
            'batch_number' => ['required', 'string', 'max:100'],
            'collection_center_id' => [
                'required',
                Rule::exists('collection_centers', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'collection_center' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255', Rule::in(config('dairyiq.uganda_districts', []))],
            'tested_by' => ['required', 'string', 'max:255'],
            'driver_name' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:100'],
            'collected_at' => ['nullable', 'date'],
            'liters_collected' => ['required', 'numeric', 'min:0'],
            'pH' => ['required', 'numeric', 'between:0,14'],
            'Temperature' => ['required', 'numeric', 'min:0', 'max:100'],
            'Taste' => ['required', 'string', Rule::in(['normal', 'acceptable', 'abnormal', 'off', 'off-taste'])],
            'Odor' => ['required', 'string', Rule::in(['fresh', 'normal', 'abnormal', 'objectionable'])],
            'Fat_Content' => ['required', 'numeric', 'min:0', 'max:20'],
            'Titratable_Acidity' => ['required', 'numeric', 'min:0', 'max:5'],
            'Protein_Content' => ['required', 'numeric', 'min:0', 'max:20'],
            'Lactose_Content' => ['required', 'numeric', 'min:0', 'max:20'],
            'TPC' => ['required', 'integer', 'min:0'],
            'SCC' => ['required', 'integer', 'min:0'],
            'Color' => ['required', 'string', Rule::in(['normal', 'creamy-white', 'white', 'abnormal'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'collection_center_id.required' => 'Please select a collection center registered to your company.',
            'collection_center_id.exists' => 'The selected collection center is invalid or does not belong to your company.',
            'vehicle_id.required' => 'Please select a vehicle registered to your company.',
            'vehicle_id.exists' => 'The selected vehicle is invalid or does not belong to your company.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function predictionPayload(): array
    {
        return $this->safe()->only([
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
        ]);
    }

    private function normalizeDistrict(mixed $district): ?string
    {
        if (! is_string($district)) {
            return null;
        }

        $normalized = Str::of($district)->trim()->squish();

        if ($normalized->isEmpty()) {
            return null;
        }

        return $normalized->lower()->title()->toString();
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = Str::of($value)->trim()->squish();

        return $normalized->isEmpty() ? null : $normalized->toString();
    }

    private function normalizeVehicleNumber(mixed $vehicleNumber): ?string
    {
        $normalized = $this->normalizeText($vehicleNumber);

        return $normalized === null ? null : Str::of($normalized)->upper()->toString();
    }
}
