import { apiClient } from './client';
import type { MoveWorkOrderPayload, MoveWorkOrderResult, WorkOrder, WorkOrderEvent } from './types';

export async function fetchWorkOrders(): Promise<WorkOrder[]> {
  const { data } = await apiClient.get<{ data: WorkOrder[] }>('/work-orders');

  return data.data;
}

export async function fetchWorkOrderEvents(workOrderId: number): Promise<WorkOrderEvent[]> {
  const { data } = await apiClient.get<{ data: WorkOrderEvent[] }>(`/work-orders/${workOrderId}/events`);

  return data.data;
}

interface CreateWorkOrderPayload {
  title: string;
  description?: string;
  production_line_id?: number | null;
  priority?: WorkOrder['priority'];
  due_date?: string | null;
}

export async function createWorkOrder(payload: CreateWorkOrderPayload): Promise<WorkOrder> {
  const { data } = await apiClient.post<{ data: WorkOrder }>('/work-orders', payload);

  return data.data;
}

interface UpdateWorkOrderPayload {
  title?: string;
  description?: string | null;
  production_line_id?: number | null;
  priority?: WorkOrder['priority'];
  due_date?: string | null;
}

export async function updateWorkOrder(id: number, payload: UpdateWorkOrderPayload): Promise<WorkOrder> {
  const { data } = await apiClient.patch<{ data: WorkOrder }>(`/work-orders/${id}`, payload);

  return data.data;
}

export async function deleteWorkOrder(id: number): Promise<void> {
  await apiClient.delete(`/work-orders/${id}`);
}

/**
 * The move endpoint expresses a drop as "goes between these two siblings" rather than a raw
 * position — the server re-reads real positions from the database and computes the result, so
 * the client never has to trust or compute a float it might race on. The response also carries
 * every position in the affected stage (a same-slot rebalance can silently shift siblings), so
 * the caller can patch its cache from this instead of refetching the whole board.
 */
export async function moveWorkOrder(id: number, payload: MoveWorkOrderPayload): Promise<MoveWorkOrderResult> {
  const { data } = await apiClient.patch<{
    data: WorkOrder;
    stage_positions: { id: number; position: number }[];
  }>(`/work-orders/${id}/move`, payload);

  return { workOrder: data.data, stagePositions: data.stage_positions };
}
