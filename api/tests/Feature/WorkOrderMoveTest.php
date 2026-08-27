<?php

namespace Tests\Feature;

use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderStage;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderMoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_to_an_empty_stage_assigns_a_position(): void
    {
        $workOrder = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 1000]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$workOrder->id}/move", ['stage' => WorkOrderStage::Done->value])
            ->assertOk()
            ->assertJsonPath('data.stage', WorkOrderStage::Done->value);

        $this->assertGreaterThan(0, $workOrder->fresh()->position);
    }

    public function test_moving_to_a_different_stage_writes_a_stage_changed_event(): void
    {
        $workOrder = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$workOrder->id}/move", ['stage' => WorkOrderStage::InProgress->value])
            ->assertOk();

        $this->assertDatabaseHas('work_order_events', [
            'work_order_id' => $workOrder->id,
            'event_type' => WorkOrderEventType::StageChanged->value,
            'from_stage' => WorkOrderStage::Queued->value,
            'to_stage' => WorkOrderStage::InProgress->value,
        ]);
    }

    public function test_reordering_within_the_same_stage_does_not_write_an_event(): void
    {
        $a = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 1000]);
        $b = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 2000]);
        Sanctum::actingAs(User::factory()->create());

        // Move $b to the front: nothing before it, $a is now the neighbor immediately after.
        $this->patchJson("/api/work-orders/{$b->id}/move", [
            'stage' => WorkOrderStage::Queued->value,
            'before_id' => null,
            'after_id' => $a->id,
        ])->assertOk();

        $this->assertDatabaseCount('work_order_events', 0);
        $this->assertTrue($b->fresh()->position < $a->fresh()->position);
    }

    public function test_dropping_between_two_cards_computes_the_midpoint(): void
    {
        $first = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 1000]);
        $last = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 2000]);
        $moving = WorkOrder::factory()->create(['stage' => WorkOrderStage::Done, 'position' => 1000]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$moving->id}/move", [
            'stage' => WorkOrderStage::Queued->value,
            'before_id' => $first->id,
            'after_id' => $last->id,
        ])->assertOk();

        $this->assertEqualsWithDelta(1500.0, $moving->fresh()->position, 0.01);
    }

    public function test_repeated_drops_at_the_same_spot_trigger_a_rebalance_and_stay_ordered(): void
    {
        $first = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 0]);
        $last = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 1000]);
        $moving = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 2000]);
        Sanctum::actingAs(User::factory()->create());

        // Repeatedly drop the same card between $first and $last — each drop halves the gap,
        // so after enough repeats the gap falls under the rebalance threshold and the whole
        // stage gets renumbered. The card should still end up strictly between its neighbors
        // no matter how many times this happens.
        for ($i = 0; $i < 15; $i++) {
            $this->patchJson("/api/work-orders/{$moving->id}/move", [
                'stage' => WorkOrderStage::Queued->value,
                'before_id' => $first->id,
                'after_id' => $last->id,
            ])->assertOk();
        }

        $first->refresh();
        $last->refresh();
        $moving->refresh();

        $this->assertTrue($first->position < $moving->position);
        $this->assertTrue($moving->position < $last->position);
    }

    public function test_the_response_includes_every_position_in_the_affected_stage(): void
    {
        $a = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 1000]);
        $b = WorkOrder::factory()->create(['stage' => WorkOrderStage::Queued, 'position' => 2000]);
        $moving = WorkOrder::factory()->create(['stage' => WorkOrderStage::Done, 'position' => 1000]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson("/api/work-orders/{$moving->id}/move", [
            'stage' => WorkOrderStage::Queued->value,
            'before_id' => $a->id,
            'after_id' => $b->id,
        ])->assertOk();

        $ids = collect($response->json('stage_positions'))->pluck('id');
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $moving->id], $ids->all());
    }

    public function test_move_requires_stage(): void
    {
        $workOrder = WorkOrder::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/work-orders/{$workOrder->id}/move", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stage']);
    }
}
