<?php

namespace App\Models;

use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderStage;
use Database\Factories\WorkOrderEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderEvent extends Model
{
    /** @use HasFactory<WorkOrderEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'event_type',
        'from_stage',
        'to_stage',
        'from_line_id',
        'to_line_id',
        'actor_id',
        'note',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => WorkOrderEventType::class,
            'from_stage' => WorkOrderStage::class,
            'to_stage' => WorkOrderStage::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function fromLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'from_line_id');
    }

    public function toLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'to_line_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
