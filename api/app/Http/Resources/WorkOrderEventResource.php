<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type->value,
            'from_stage' => $this->from_stage?->value,
            'to_stage' => $this->to_stage?->value,
            'from_line' => new ProductionLineResource($this->whenLoaded('fromLine')),
            'to_line' => new ProductionLineResource($this->whenLoaded('toLine')),
            'actor_name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'note' => $this->note,
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
