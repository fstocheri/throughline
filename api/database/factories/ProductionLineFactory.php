<?php

namespace Database\Factories;

use App\Models\ProductionLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionLine>
 */
class ProductionLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = $this->faker->unique()->numberBetween(1, 99);

        return [
            'name' => "Line {$number}",
            'code' => 'L'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'is_active' => true,
        ];
    }
}
