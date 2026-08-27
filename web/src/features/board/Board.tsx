import {
  DndContext,
  PointerSensor,
  closestCorners,
  useSensor,
  useSensors,
  type DragEndEvent,
} from '@dnd-kit/core';
import { useState } from 'react';
import { STAGES, type WorkOrder, type WorkOrderStage } from '../../api/types';
import { WorkOrderDetailDrawer } from '../work-orders/WorkOrderDetailDrawer';
import { Column } from './Column';
import { NewWorkOrderForm } from './NewWorkOrderForm';
import { useMoveWorkOrderMutation } from './useMoveWorkOrderMutation';
import { useWorkOrdersQuery } from './useWorkOrdersQuery';

const STAGE_VALUES = STAGES.map((stage) => stage.value);

function byStage(workOrders: WorkOrder[], stage: WorkOrderStage) {
  return workOrders.filter((workOrder) => workOrder.stage === stage).sort((a, b) => a.position - b.position);
}

export function Board() {
  const { data: workOrders, isLoading, isError } = useWorkOrdersQuery();
  const moveMutation = useMoveWorkOrderMutation();
  const [openWorkOrder, setOpenWorkOrder] = useState<WorkOrder | null>(null);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
  );

  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || !workOrders) return;

    const activeWorkOrder = workOrders.find((workOrder) => workOrder.id === active.id);
    if (!activeWorkOrder) return;

    // `over.id` is either another card's id, or the column's own droppable id (a stage value)
    // when the card is dropped on empty column space.
    const overWorkOrder = workOrders.find((workOrder) => workOrder.id === over.id);
    const targetStage = overWorkOrder
      ? overWorkOrder.stage
      : STAGE_VALUES.includes(over.id as WorkOrderStage)
        ? (over.id as WorkOrderStage)
        : null;

    if (!targetStage) return;

    const siblings = byStage(workOrders, targetStage).filter(
      (workOrder) => workOrder.id !== activeWorkOrder.id,
    );

    let beforeId: number | null;
    let afterId: number | null;

    if (overWorkOrder && overWorkOrder.id !== activeWorkOrder.id) {
      // Dropped on a card: land immediately before it. (A simplification — this doesn't
      // distinguish dropping on the top vs. bottom half of the target card — but it's the
      // right amount of precision for a demo board with a handful of cards per column.)
      const overIndex = siblings.findIndex((workOrder) => workOrder.id === overWorkOrder.id);
      afterId = overWorkOrder.id;
      beforeId = overIndex > 0 ? siblings[overIndex - 1].id : null;
    } else {
      // Dropped on empty column space: land at the end.
      beforeId = siblings.length > 0 ? siblings[siblings.length - 1].id : null;
      afterId = null;
    }

    const unchanged =
      activeWorkOrder.stage === targetStage &&
      beforeId === null &&
      afterId === null &&
      siblings.length === 0;

    if (unchanged) return;

    moveMutation.mutate({
      id: activeWorkOrder.id,
      payload: { stage: targetStage, before_id: beforeId, after_id: afterId },
    });
  }

  if (isLoading) return <div className="page-loading">Loading board&hellip;</div>;
  if (isError || !workOrders) return <div className="page-loading">Couldn&rsquo;t load the board.</div>;

  return (
    <>
      <div className="board-toolbar">
        <NewWorkOrderForm />
      </div>

      <DndContext sensors={sensors} collisionDetection={closestCorners} onDragEnd={handleDragEnd}>
        <div className="board">
          {STAGES.map((stage) => (
            <Column
              key={stage.value}
              stage={stage.value}
              label={stage.label}
              workOrders={byStage(workOrders, stage.value)}
              onOpenCard={setOpenWorkOrder}
            />
          ))}
        </div>
      </DndContext>

      {openWorkOrder && (
        <WorkOrderDetailDrawer workOrder={openWorkOrder} onClose={() => setOpenWorkOrder(null)} />
      )}
    </>
  );
}
