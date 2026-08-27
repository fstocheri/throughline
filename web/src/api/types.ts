export type WorkOrderStage = 'queued' | 'in_progress' | 'blocked' | 'done';
export type WorkOrderPriority = 'low' | 'normal' | 'high' | 'urgent';

export const STAGES: { value: WorkOrderStage; label: string }[] = [
  { value: 'queued', label: 'Queued' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'blocked', label: 'Blocked' },
  { value: 'done', label: 'Done' },
];

export interface ProductionLine {
  id: number;
  name: string;
  code: string;
  is_active: boolean;
}

export interface WorkOrder {
  id: number;
  work_order_number: string;
  title: string;
  description: string | null;
  stage: WorkOrderStage;
  stage_label: string;
  priority: WorkOrderPriority;
  priority_label: string;
  position: number;
  due_date: string | null;
  started_at: string | null;
  completed_at: string | null;
  production_line: ProductionLine | null;
  assignee_name: string | null;
  created_at: string;
}

export interface WorkOrderEvent {
  id: number;
  event_type:
    | 'created'
    | 'stage_changed'
    | 'line_changed'
    | 'priority_changed'
    | 'due_date_changed'
    | 'blocked'
    | 'unblocked';
  from_stage: WorkOrderStage | null;
  to_stage: WorkOrderStage | null;
  from_line: ProductionLine | null;
  to_line: ProductionLine | null;
  actor_name: string | null;
  note: string | null;
  occurred_at: string;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
}

export interface MoveWorkOrderPayload {
  stage: WorkOrderStage;
  before_id: number | null;
  after_id: number | null;
}

export interface StagePosition {
  id: number;
  position: number;
}

export interface MoveWorkOrderResult {
  workOrder: WorkOrder;
  stagePositions: StagePosition[];
}
