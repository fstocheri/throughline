<?php

namespace App\Http\Controllers\Api;

use App\Actions\MoveWorkOrder;
use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveWorkOrderRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Resources\WorkOrderEventResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::query()->with('productionLine')->orderBy('stage')->orderBy('position');

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage'));
        }

        if ($request->filled('production_line_id')) {
            $query->where('production_line_id', $request->integer('production_line_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        return WorkOrderResource::collection($query->get());
    }

    public function store(StoreWorkOrderRequest $request)
    {
        $data = $request->validated();

        $maxPosition = WorkOrder::where('stage', WorkOrderStage::Queued->value)->max('position') ?? 0;

        $workOrder = WorkOrder::create([
            ...$data,
            'work_order_number' => $this->nextWorkOrderNumber(),
            'stage' => WorkOrderStage::Queued,
            'priority' => $data['priority'] ?? WorkOrderPriority::Normal,
            'position' => $maxPosition + 1000,
            'created_by' => $request->user()->id,
        ]);

        WorkOrderEvent::create([
            'work_order_id' => $workOrder->id,
            'event_type' => WorkOrderEventType::Created,
            'to_stage' => $workOrder->stage,
            'actor_id' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        return new WorkOrderResource($workOrder->load('productionLine'));
    }

    public function show(WorkOrder $workOrder)
    {
        return new WorkOrderResource($workOrder->load('productionLine', 'assignee'));
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $workOrder, $request) {
            $this->logFieldChanges($workOrder, $data, $request->user()->id);

            $workOrder->update($data);
        });

        return new WorkOrderResource($workOrder->fresh(['productionLine']));
    }

    public function move(MoveWorkOrderRequest $request, WorkOrder $workOrder, MoveWorkOrder $action)
    {
        $stage = WorkOrderStage::from($request->validated('stage'));

        $workOrder = $action->handle(
            $workOrder,
            $stage,
            $request->validated('before_id'),
            $request->validated('after_id'),
            $request->user(),
        );

        // A rebalance (see MoveWorkOrder) can silently shift other cards' positions in the
        // same stage. Rather than have the client guess whether that happened, hand back every
        // position in the affected stage in this one response — the client patches its cache
        // from this instead of refetching the whole board after every drag.
        $stagePositions = WorkOrder::where('stage', $stage->value)
            ->orderBy('position')
            ->get(['id', 'position']);

        return (new WorkOrderResource($workOrder->load('productionLine')))
            ->additional(['stage_positions' => $stagePositions]);
    }

    public function events(WorkOrder $workOrder)
    {
        return WorkOrderEventResource::collection(
            $workOrder->events()->with(['fromLine', 'toLine', 'actor'])->get()
        );
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();

        return response()->noContent();
    }

    /**
     * Compare the incoming update against current values and write one ledger row per field
     * that actually changed — never for fields the request didn't touch or that resolved to
     * the same value, so the audit trail stays a record of real changes.
     */
    private function logFieldChanges(WorkOrder $workOrder, array $data, int $actorId): void
    {
        if (array_key_exists('production_line_id', $data) && $data['production_line_id'] != $workOrder->production_line_id) {
            WorkOrderEvent::create([
                'work_order_id' => $workOrder->id,
                'event_type' => WorkOrderEventType::LineChanged,
                'from_line_id' => $workOrder->production_line_id,
                'to_line_id' => $data['production_line_id'],
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);
        }

        if (array_key_exists('priority', $data) && $data['priority'] !== $workOrder->priority->value) {
            WorkOrderEvent::create([
                'work_order_id' => $workOrder->id,
                'event_type' => WorkOrderEventType::PriorityChanged,
                'note' => "{$workOrder->priority->value} -> {$data['priority']}",
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);
        }

        if (array_key_exists('due_date', $data)) {
            $newDueDate = $data['due_date'] ? (string) $data['due_date'] : null;
            $currentDueDate = $workOrder->due_date?->toDateString();

            if ($newDueDate !== $currentDueDate) {
                WorkOrderEvent::create([
                    'work_order_id' => $workOrder->id,
                    'event_type' => WorkOrderEventType::DueDateChanged,
                    'note' => ($currentDueDate ?? 'none').' -> '.($newDueDate ?? 'none'),
                    'actor_id' => $actorId,
                    'occurred_at' => now(),
                ]);
            }
        }
    }

    private function nextWorkOrderNumber(): string
    {
        return 'WO-'.now()->format('Y').'-'.Str::padLeft((string) (WorkOrder::withTrashed()->count() + 1), 4, '0');
    }
}
