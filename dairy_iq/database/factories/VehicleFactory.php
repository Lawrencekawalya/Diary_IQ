<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plate_number' => 'UB'.fake()->randomLetter().' '.fake()->numerify('###').fake()->randomLetter(),
            'driver_name' => fake()->name(),
            'is_active' => true,
        ];
    }
}
