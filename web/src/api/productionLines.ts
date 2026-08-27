import { apiClient } from './client';
import type { ProductionLine } from './types';

export async function fetchProductionLines(): Promise<ProductionLine[]> {
  const { data } = await apiClient.get<{ data: ProductionLine[] }>('/production-lines');

  return data.data;
}
