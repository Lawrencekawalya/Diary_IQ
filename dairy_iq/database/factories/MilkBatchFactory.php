<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MilkBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilkBatch>
 */
class MilkBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => null,
            'batch_number' => fake()->unique()->bothify('BATCH-####-??'),
            'collection_center' => fake()->city().' Collection Center',
            'district' => fake()->city(),
            'tested_by' => fake()->name(),
            'collected_at' => fake()->dateTimeBetween('-30 days'),
            'liters_collected' => fake()->randomFloat(2, 10, 500),
            'ph' => 6.70,
            'temperature' => 4.00,
            'taste' => 1,
            'odor' => 1,
            'fat_content' => 3.80,
            'titratable_acidity' => 0.15,
            'protein_content' => 3.30,
            'lactose_content' => 4.80,
            'tpc' => 50000,
            'scc' => 200000,
            'color' => 1,
            'sensory_inputs' => [
                'taste' => 'Normal',
                'odor' => 'Normal',
                'color' => 'Normal',
            ],
            'encoded_sensory_values' => [
                'Taste' => 1,
                'Odor' => 1,
                'Color' => 1,
            ],
            'prediction' => 'High',
            'ml_prediction' => 'High',
            'confidence' => 0.9333,
            'probabilities' => [
                'Low' => 0.0000,
                'Medium' => 0.0667,
                'High' => 0.9333,
            ],
            'standards_observations' => [],
            'standards_quality_gate' => [
                'passed' => true,
                'downgraded' => false,
            ],
            'feature_status' => [
                'pH' => true,
                'Temperature' => true,
                'Taste' => true,
                'Odor' => true,
                'Fat_Content' => true,
                'Titratable_Acidity' => true,
                'Protein_Content' => true,
                'Lactose_Content' => true,
                'TPC' => true,
                'SCC' => true,
                'Color' => true,
            ],
            'model_metadata' => [
                'model_type' => 'RandomForestClassifier',
                'feature_contract_version' => 'approved_thesis_11_features_v1',
            ],
            'record_schema_version' => 'milk_batch_prediction_v1',
            'source' => 'laravel',
        ];
    }
}
