import type { WorkOrder, WorkOrderEvent } from '../../api/types';
import { useWorkOrderEventsQuery } from './useWorkOrderEventsQuery';

const STAGE_LABEL: Record<string, string> = {
  queued: 'Queued',
  in_progress: 'In Progress',
  blocked: 'Blocked',
  done: 'Done',
};

function describeEvent(event: WorkOrderEvent): string {
  switch (event.event_type) {
    case 'created':
      return `Created in ${STAGE_LABEL[event.to_stage ?? ''] ?? event.to_stage}`;
    case 'stage_changed':
      return `Moved ${STAGE_LABEL[event.from_stage ?? ''] ?? event.from_stage} → ${STAGE_LABEL[event.to_stage ?? ''] ?? event.to_stage}`;
    case 'line_changed':
      return `Reassigned ${event.from_line?.name ?? 'unassigned'} → ${event.to_line?.name ?? 'unassigned'}`;
    case 'priority_changed':
      return `Priority changed${event.note ? `: ${event.note}` : ''}`;
    case 'due_date_changed':
      return `Due date changed${event.note ? `: ${event.note}` : ''}`;
    case 'blocked':
      return `Blocked${event.note ? ` — ${event.note}` : ''}`;
    case 'unblocked':
      return 'Unblocked';
    default:
      return event.event_type;
  }
}

export function WorkOrderDetailDrawer({
  workOrder,
  onClose,
}: {
  workOrder: WorkOrder;
  onClose: () => void;
}) {
  const { data: events, isLoading } = useWorkOrderEventsQuery(workOrder.id);

  return (
    <div className="drawer-backdrop" onClick={onClose}>
      <aside className="drawer" onClick={(event) => event.stopPropagation()}>
        <header className="drawer__header">
          <div>
            <span className="work-order-card__number">{workOrder.work_order_number}</span>
            <h2>{workOrder.title}</h2>
          </div>
          <button type="button" className="drawer__close" onClick={onClose} aria-label="Close">
            &times;
          </button>
        </header>

        <dl className="drawer__facts">
          <div>
            <dt>Stage</dt>
            <dd>{workOrder.stage_label}</dd>
          </div>
          <div>
            <dt>Priority</dt>
            <dd>{workOrder.priority_label}</dd>
          </div>
          <div>
            <dt>Line</dt>
            <dd>{workOrder.production_line?.name ?? 'Unassigned'}</dd>
          </div>
          <div>
            <dt>Due</dt>
            <dd>{workOrder.due_date ?? '—'}</dd>
          </div>
        </dl>

        {workOrder.description && <p className="drawer__description">{workOrder.description}</p>}

        <section className="drawer__events">
          <h3>Audit trail</h3>
          {isLoading && <p className="drawer__events-loading">Loading history&hellip;</p>}
          <ul>
            {events?.map((event) => (
              <li key={event.id}>
                <span className="drawer__event-time">
                  {new Date(event.occurred_at).toLocaleString()}
                </span>
                <span className="drawer__event-description">{describeEvent(event)}</span>
              </li>
            ))}
          </ul>
        </section>
      </aside>
    </div>
  );
}
