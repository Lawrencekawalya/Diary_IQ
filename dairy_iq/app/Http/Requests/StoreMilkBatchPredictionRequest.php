<?php

namespace App\Http\Requests;

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
        return [
            'batch_number' => ['required', 'string', 'max:100'],
            'collection_center' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255', Rule::in(config('dairyiq.uganda_districts', []))],
            'tested_by' => ['required', 'string', 'max:255'],
            'driver_name' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['required', 'string', 'max:100'],
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
