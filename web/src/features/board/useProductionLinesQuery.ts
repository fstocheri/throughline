import { useQuery } from '@tanstack/react-query';
import { fetchProductionLines } from '../../api/productionLines';

export function useProductionLinesQuery() {
  return useQuery({
    queryKey: ['production-lines'],
    queryFn: fetchProductionLines,
  });
}
