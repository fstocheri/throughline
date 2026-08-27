<?php

namespace App\Http\Requests;

use App\Enums\WorkOrderStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('move', $this->route('work_order'));
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(WorkOrderStage::class)],
            'before_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'after_id' => ['nullable', 'integer', 'exists:work_orders,id'],
        ];
    }
}
