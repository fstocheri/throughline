<?php

namespace App\Enums;

enum WorkOrderEventType: string
{
    case Created = 'created';
    case StageChanged = 'stage_changed';
    case LineChanged = 'line_changed';
    case PriorityChanged = 'priority_changed';
    case DueDateChanged = 'due_date_changed';
    case Blocked = 'blocked';
    case Unblocked = 'unblocked';
}
