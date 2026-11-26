import type { ProductionQueueItem } from '../../api/types';

export function needsWasteReminder(
    order: Pick<ProductionQueueItem, 'status' | 'has_waste'>
): boolean {
    return order.status === 3 && !order.has_waste;
}
