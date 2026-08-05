<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Subbuilding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subbuilding>
 */
class SubbuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'name' => 'Ruang '.fake()->numberBetween(100, 399),
        ];
    }
}
