import { useQuery } from '@tanstack/react-query';
import { fetchWorkOrders } from '../../api/workOrders';

export const WORK_ORDERS_QUERY_KEY = ['work-orders'] as const;

export function useWorkOrdersQuery() {
  return useQuery({
    queryKey: WORK_ORDERS_QUERY_KEY,
    queryFn: fetchWorkOrders,
  });
}
