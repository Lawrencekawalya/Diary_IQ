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
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('district')) {
            $this->merge([
                'district' => $this->normalizeDistrict($this->input('district')),
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
            'batch_number' => ['nullable', 'string', 'max:100'],
            'collection_center' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'tested_by' => ['nullable', 'string', 'max:255'],
            'collected_at' => ['nullable', 'date'],
            'liters_collected' => ['nullable', 'numeric', 'min:0'],
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
}
