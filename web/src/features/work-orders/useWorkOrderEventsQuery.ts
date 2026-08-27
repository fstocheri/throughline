import { useQuery } from '@tanstack/react-query';
import { fetchWorkOrderEvents } from '../../api/workOrders';

export function useWorkOrderEventsQuery(workOrderId: number) {
  return useQuery({
    queryKey: ['work-order-events', workOrderId],
    queryFn: () => fetchWorkOrderEvents(workOrderId),
  });
}
