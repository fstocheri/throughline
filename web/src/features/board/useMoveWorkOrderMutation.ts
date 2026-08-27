import { useMutation, useQueryClient } from '@tanstack/react-query';
import { moveWorkOrder } from '../../api/workOrders';
import type { MoveWorkOrderPayload, MoveWorkOrderResult, WorkOrder } from '../../api/types';
import { WORK_ORDERS_QUERY_KEY } from './useWorkOrdersQuery';

interface MoveVariables {
  id: number;
  payload: MoveWorkOrderPayload;
}

interface MutationContext {
  previous: WorkOrder[] | undefined;
}

/**
 * Optimistic drag-and-drop: the card jumps to its new spot immediately, before the network
 * call resolves, then either gets reconciled with the server's canonical positions (onSuccess)
 * or snapped back to exactly where it was (onError). The `position` this computes locally is
 * only ever a guess for the frame or two before the response lands — it never gets persisted,
 * so it doesn't need to match the server's math exactly.
 */
export function useMoveWorkOrderMutation() {
  const queryClient = useQueryClient();

  return useMutation<MoveWorkOrderResult, Error, MoveVariables, MutationContext>({
    mutationFn: ({ id, payload }) => moveWorkOrder(id, payload),

    onMutate: async ({ id, payload }) => {
      await queryClient.cancelQueries({ queryKey: WORK_ORDERS_QUERY_KEY });

      const previous = queryClient.getQueryData<WorkOrder[]>(WORK_ORDERS_QUERY_KEY);

      queryClient.setQueryData<WorkOrder[]>(WORK_ORDERS_QUERY_KEY, (current) => {
        if (!current) return current;

        const moving = current.find((workOrder) => workOrder.id === id);
        if (!moving) return current;

        const before = payload.before_id
          ? current.find((workOrder) => workOrder.id === payload.before_id)
          : undefined;
        const after = payload.after_id
          ? current.find((workOrder) => workOrder.id === payload.after_id)
          : undefined;

        const guessedPosition =
          before && after
            ? (before.position + after.position) / 2
            : before
              ? before.position + 1
              : after
                ? after.position - 1
                : moving.position;

        return current.map((workOrder) =>
          workOrder.id === id
            ? { ...workOrder, stage: payload.stage, position: guessedPosition }
            : workOrder,
        );
      });

      return { previous };
    },

    onError: (_error, _variables, context) => {
      if (context?.previous) {
        queryClient.setQueryData(WORK_ORDERS_QUERY_KEY, context.previous);
      }
    },

    onSuccess: ({ stagePositions }, { payload }) => {
      const positionById = new Map(stagePositions.map((entry) => [entry.id, entry.position]));

      queryClient.setQueryData<WorkOrder[]>(WORK_ORDERS_QUERY_KEY, (current) =>
        current?.map((workOrder) =>
          positionById.has(workOrder.id)
            ? { ...workOrder, stage: payload.stage, position: positionById.get(workOrder.id)! }
            : workOrder,
        ),
      );
    },
  });
}
