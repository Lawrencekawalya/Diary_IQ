<?php

namespace Database\Factories;

use App\Models\CollectionCenter;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionCenter>
 */
class CollectionCenterFactory extends Factory
{
    protected $model = CollectionCenter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->city().' Collection Center',
            'district' => fake()->city(),
            'is_active' => true,
        ];
    }
}
