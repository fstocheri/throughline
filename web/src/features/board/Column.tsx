import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import type { WorkOrder, WorkOrderStage } from '../../api/types';
import { WorkOrderCard } from './WorkOrderCard';

export function Column({
  stage,
  label,
  workOrders,
  onOpenCard,
}: {
  stage: WorkOrderStage;
  label: string;
  workOrders: WorkOrder[];
  onOpenCard: (workOrder: WorkOrder) => void;
}) {
  const { setNodeRef, isOver } = useDroppable({ id: stage });

  return (
    <div className={`board-column ${isOver ? 'board-column--over' : ''}`}>
      <div className="board-column__header">
        <h2>{label}</h2>
        <span className="board-column__count">{workOrders.length}</span>
      </div>
      <div ref={setNodeRef} className="board-column__body">
        <SortableContext
          items={workOrders.map((workOrder) => workOrder.id)}
          strategy={verticalListSortingStrategy}
        >
          {workOrders.map((workOrder) => (
            <WorkOrderCard key={workOrder.id} workOrder={workOrder} onOpen={onOpenCard} />
          ))}
        </SortableContext>
        {workOrders.length === 0 && <p className="board-column__empty">No work orders</p>}
      </div>
    </div>
  );
}
