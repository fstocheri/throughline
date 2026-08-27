<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

/**
 * Every authenticated request goes through the single seeded demo account for now — this
 * policy exists so authorization is a named, testable concept from day one rather than an
 * afterthought, ready to differentiate roles (e.g. a read-only viewer) without touching
 * controllers later.
 */
class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return true;
    }

    public function move(User $user, WorkOrder $workOrder): bool
    {
        return true;
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return true;
    }
}
