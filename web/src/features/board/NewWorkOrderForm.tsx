import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { createWorkOrder } from '../../api/workOrders';
import type { WorkOrderPriority } from '../../api/types';
import { useProductionLinesQuery } from './useProductionLinesQuery';
import { WORK_ORDERS_QUERY_KEY } from './useWorkOrdersQuery';

export function NewWorkOrderForm() {
  const [isOpen, setIsOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [priority, setPriority] = useState<WorkOrderPriority>('normal');
  const [productionLineId, setProductionLineId] = useState<string>('');

  const { data: lines } = useProductionLinesQuery();
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: () =>
      createWorkOrder({
        title,
        priority,
        production_line_id: productionLineId ? Number(productionLineId) : null,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: WORK_ORDERS_QUERY_KEY });
      setTitle('');
      setPriority('normal');
      setProductionLineId('');
      setIsOpen(false);
    },
  });

  function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!title.trim()) return;
    mutation.mutate();
  }

  if (!isOpen) {
    return (
      <button type="button" className="new-work-order-trigger" onClick={() => setIsOpen(true)}>
        + New work order
      </button>
    );
  }

  return (
    <form className="new-work-order-form" onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="What needs to happen?"
        value={title}
        onChange={(event) => setTitle(event.target.value)}
        autoFocus
        required
      />
      <select value={priority} onChange={(event) => setPriority(event.target.value as WorkOrderPriority)}>
        <option value="low">Low</option>
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      <select value={productionLineId} onChange={(event) => setProductionLineId(event.target.value)}>
        <option value="">No line</option>
        {lines?.map((line) => (
          <option key={line.id} value={line.id}>
            {line.name}
          </option>
        ))}
      </select>
      <button type="submit" disabled={mutation.isPending}>
        {mutation.isPending ? 'Adding…' : 'Add'}
      </button>
      <button type="button" className="new-work-order-cancel" onClick={() => setIsOpen(false)}>
        Cancel
      </button>
    </form>
  );
}
