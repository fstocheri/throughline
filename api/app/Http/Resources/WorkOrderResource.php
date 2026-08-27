<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_number' => $this->work_order_number,
            'title' => $this->title,
            'description' => $this->description,
            'stage' => $this->stage->value,
            'stage_label' => $this->stage->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'position' => $this->position,
            'due_date' => $this->due_date?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'production_line' => new ProductionLineResource($this->whenLoaded('productionLine')),
            'assignee_name' => $this->whenLoaded('assignee', fn () => $this->assignee?->name),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
