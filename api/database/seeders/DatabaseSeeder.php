<?php

namespace Database\Seeders;

use App\Enums\WorkOrderEventType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStage;
use App\Models\ProductionLine;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a walkthrough-ready board. The real login for the live deploy comes from
     * `php artisan demo:ensure` (DEMO_EMAIL/DEMO_PASSWORD) — this local user only exists so
     * `migrate:fresh --seed` gives you something to log in with in local dev.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@throughline.test'],
            ['name' => 'Demo User', 'password' => bcrypt('password')],
        );

        $lines = collect(['Assembly A', 'Assembly B', 'Packaging', 'Finishing'])
            ->map(fn (string $name, int $i) => ProductionLine::create([
                'name' => $name,
                'code' => 'L'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]));

        $board = [
            WorkOrderStage::Queued->value => [
                ['Cut panels for order #4821', WorkOrderPriority::Normal],
                ['Prep raw stock — oak batch', WorkOrderPriority::Low],
                ['Rework rejected units from QA', WorkOrderPriority::Urgent],
                ['Restock fasteners bin 12', WorkOrderPriority::Low],
            ],
            WorkOrderStage::InProgress->value => [
                ['Assemble frame batch 7', WorkOrderPriority::High],
                ['Weld subframe run 3', WorkOrderPriority::Normal],
                ['Paint booth — run 14', WorkOrderPriority::High],
            ],
            WorkOrderStage::Blocked->value => [
                ['Install hardware kit — awaiting parts', WorkOrderPriority::Urgent],
            ],
            WorkOrderStage::Done->value => [
                ['Package order #4790', WorkOrderPriority::Normal],
                ['Final inspection — batch 5', WorkOrderPriority::Normal],
                ['Label and palletize run 12', WorkOrderPriority::Low],
            ],
        ];

        $number = 1;

        foreach ($board as $stage => $cards) {
            $position = 1000.0;

            foreach ($cards as [$title, $priority]) {
                $workOrder = WorkOrder::create([
                    'work_order_number' => 'WO-2026-'.str_pad((string) $number++, 4, '0', STR_PAD_LEFT),
                    'title' => $title,
                    'production_line_id' => $lines->random()->id,
                    'stage' => $stage,
                    'priority' => $priority,
                    'position' => $position,
                    'due_date' => now()->addDays(random_int(1, 14)),
                    'created_by' => $user->id,
                ]);

                WorkOrderEvent::create([
                    'work_order_id' => $workOrder->id,
                    'event_type' => WorkOrderEventType::Created,
                    'to_stage' => $workOrder->stage,
                    'actor_id' => $user->id,
                    'occurred_at' => $workOrder->created_at,
                ]);

                if ($stage !== WorkOrderStage::Queued->value) {
                    WorkOrderEvent::create([
                        'work_order_id' => $workOrder->id,
                        'event_type' => WorkOrderEventType::StageChanged,
                        'from_stage' => WorkOrderStage::Queued,
                        'to_stage' => $stage,
                        'actor_id' => $user->id,
                        'occurred_at' => now()->subHours(random_int(1, 48)),
                    ]);
                }

                if ($stage === WorkOrderStage::Blocked->value) {
                    WorkOrderEvent::create([
                        'work_order_id' => $workOrder->id,
                        'event_type' => WorkOrderEventType::Blocked,
                        'note' => 'Waiting on hardware kit delivery from supplier',
                        'actor_id' => $user->id,
                        'occurred_at' => now()->subHours(3),
                    ]);
                }

                $position += 1000;
            }
        }
    }
}
