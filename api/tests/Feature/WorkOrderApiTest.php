<?php

namespace Tests\Feature;

use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStage;
use App\Models\ProductionLine;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_work_orders(): void
    {
        $this->getJson('/api/work-orders')->assertUnauthorized();
    }

    public function test_it_lists_work_orders_with_their_production_line(): void
    {
        $line = ProductionLine::factory()->create();
        WorkOrder::factory()->count(2)->create(['production_line_id' => $line->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/work-orders')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.production_line.id', $line->id);
    }

    public function test_it_filters_by_stage(): void
    {
        WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued]);
        WorkOrder::factory()->create(['stage' => WorkOrderStage::Done]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/work-orders?stage=done')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.stage', 'done');
    }

    public function test_it_creates_a_work_order_in_the_queued_stage(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/work-orders', [
            'title' => 'Assemble frame batch 9',
            'priority' => WorkOrderPriority::High->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.stage', WorkOrderStage::Queued->value)
            ->assertJsonPath('data.priority', WorkOrderPriority::High->value);

        $this->assertDatabaseHas('work_order_events', [
            'work_order_id' => $response->json('data.id'),
            'event_type' => WorkOrderEventType::Created->value,
        ]);
    }

    public function test_updating_priority_writes_an_event(): void
    {
        $workOrder = WorkOrder::factory()->create(['priority' => WorkOrderPriority::Low]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$workOrder->id}", ['priority' => WorkOrderPriority::Urgent->value])
            ->assertOk()
            ->assertJsonPath('data.priority', WorkOrderPriority::Urgent->value);

        $this->assertDatabaseHas('work_order_events', [
            'work_order_id' => $workOrder->id,
            'event_type' => WorkOrderEventType::PriorityChanged->value,
        ]);
    }

    public function test_updating_to_the_same_priority_does_not_write_an_event(): void
    {
        $workOrder = WorkOrder::factory()->create(['priority' => WorkOrderPriority::Normal]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$workOrder->id}", ['priority' => WorkOrderPriority::Normal->value])
            ->assertOk();

        $this->assertDatabaseCount('work_order_events', 0);
    }

    public function test_it_returns_the_event_history_for_a_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $workOrder->events()->create([
            'event_type' => WorkOrderEventType::Created,
            'to_stage' => WorkOrderStage::Queued,
            'occurred_at' => now(),
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/work-orders/{$workOrder->id}/events")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
