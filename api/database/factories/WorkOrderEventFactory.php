<?php

namespace Database\Factories;

use App\Enums\WorkOrderEventType;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderEvent>
 */
class WorkOrderEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'event_type' => WorkOrderEventType::Created,
            'occurred_at' => now(),
        ];
    }
}
