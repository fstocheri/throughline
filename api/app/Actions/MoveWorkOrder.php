<?php

namespace App\Actions;

use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderStage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MoveWorkOrder
{
    /**
     * Spacing between positions for freshly-assigned/rebalanced rows. Large enough that many
     * successive same-spot drops (each halving the gap to its neighbor) can happen before the
     * gap becomes too small to represent as a distinct double.
     */
    private const GAP = 1000.0;

    /**
     * Below this gap between two neighboring positions, recompute the whole stage's ordering
     * from scratch rather than keep halving — halving indefinitely eventually collides on float
     * precision.
     */
    private const MIN_GAP = 1.0;

    /**
     * Move a work order to a stage/position, expressed as "goes between these two siblings" so
     * the client never has to compute or trust a raw position value. Positions are always
     * re-read from the database inside the transaction, never taken from the request.
     */
    public function handle(
        WorkOrder $workOrder,
        WorkOrderStage $stage,
        ?int $beforeId,
        ?int $afterId,
        ?User $actor = null,
    ): WorkOrder {
        return DB::transaction(function () use ($workOrder, $stage, $beforeId, $afterId, $actor) {
            $fromStage = $workOrder->stage;

            $workOrder->forceFill([
                'stage' => $stage,
                'position' => $this->resolvePosition($workOrder, $stage, $beforeId, $afterId),
            ])->save();

            // A same-stage reorder has no audit value on its own — only a real stage
            // transition is worth a ledger row, same philosophy as GreenStock's movement log.
            if ($fromStage !== $stage) {
                WorkOrderEvent::create([
                    'work_order_id' => $workOrder->id,
                    'event_type' => WorkOrderEventType::StageChanged,
                    'from_stage' => $fromStage,
                    'to_stage' => $stage,
                    'actor_id' => $actor?->id,
                    'occurred_at' => now(),
                ]);
            }

            return $workOrder->refresh();
        });
    }

    private function resolvePosition(WorkOrder $workOrder, WorkOrderStage $stage, ?int $beforeId, ?int $afterId): float
    {
        $siblings = $this->siblings($workOrder, $stage);

        $before = $beforeId ? $siblings->firstWhere('id', $beforeId) : null;
        $after = $afterId ? $siblings->firstWhere('id', $afterId) : null;

        if ($before && $after && ($after->position - $before->position) < self::MIN_GAP) {
            $this->rebalance($siblings);
            $siblings = $this->siblings($workOrder, $stage);
            $before = $beforeId ? $siblings->firstWhere('id', $beforeId) : null;
            $after = $afterId ? $siblings->firstWhere('id', $afterId) : null;
        }

        return match (true) {
            $before && $after => ($before->position + $after->position) / 2,
            (bool) $before => $before->position + self::GAP,
            (bool) $after => $after->position - self::GAP,
            $siblings->isEmpty() => self::GAP,
            default => $siblings->max('position') + self::GAP,
        };
    }

    /**
     * @return Collection<int, WorkOrder>
     */
    private function siblings(WorkOrder $workOrder, WorkOrderStage $stage): Collection
    {
        return WorkOrder::query()
            ->where('stage', $stage->value)
            ->where('id', '!=', $workOrder->id)
            ->orderBy('position')
            ->lockForUpdate()
            ->get(['id', 'position']);
    }

    /**
     * @param  Collection<int, WorkOrder>  $siblings
     */
    private function rebalance(Collection $siblings): void
    {
        $position = self::GAP;

        foreach ($siblings as $sibling) {
            WorkOrder::whereKey($sibling->id)->update(['position' => $position]);
            $position += self::GAP;
        }
    }
}
