import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { WorkOrder } from '../../api/types';

const PRIORITY_CLASS: Record<WorkOrder['priority'], string> = {
  low: 'priority-low',
  normal: 'priority-normal',
  high: 'priority-high',
  urgent: 'priority-urgent',
};

export function WorkOrderCard({
  workOrder,
  onOpen,
}: {
  workOrder: WorkOrder;
  onOpen: (workOrder: WorkOrder) => void;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: workOrder.id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className="work-order-card"
      onClick={() => onOpen(workOrder)}
    >
      <div className="work-order-card__header">
        <span className="work-order-card__number">{workOrder.work_order_number}</span>
        <span className={`priority-badge ${PRIORITY_CLASS[workOrder.priority]}`}>
          {workOrder.priority_label}
        </span>
      </div>
      <p className="work-order-card__title">{workOrder.title}</p>
      <div className="work-order-card__footer">
        {workOrder.production_line && (
          <span className="line-chip">{workOrder.production_line.code}</span>
        )}
        {workOrder.due_date && <span className="due-date">Due {workOrder.due_date}</span>}
      </div>
    </div>
  );
}
