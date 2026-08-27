<?php

namespace Database\Factories;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStage;
use App\Models\ProductionLine;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_order_number' => 'WO-'.$this->faker->unique()->numerify('#####'),
            'title' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->optional()->sentence(),
            'production_line_id' => ProductionLine::factory(),
            'stage' => WorkOrderStage::Queued,
            'priority' => $this->faker->randomElement(WorkOrderPriority::cases()),
            'position' => $this->faker->randomFloat(2, 0, 100000),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'created_by' => User::factory(),
        ];
    }
}
