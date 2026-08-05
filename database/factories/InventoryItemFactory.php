<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\ItemType;
use App\Models\Subbuilding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subbuilding_id' => Subbuilding::factory(),
            'item_type_id' => ItemType::factory(),
            'brand' => fake()->word(),
            'specification' => fake()->sentence(),
            'acquisition_source' => fake()->randomElement(['APBN', 'Hibah', 'Mandiri']),
            'entry_date' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
            'nup_code' => fake()->unique()->numerify('3.05.##.##.###'),
            'faculty_code' => fake()->unique()->bothify('FT-####-????'),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
