import type { Order } from '../../api/types';

/** Legacy orders without a linked product cannot be re-quoted safely. */
export function isOrderEditable(order: Order): boolean {
    return order.status === 0 && order.product_id !== null;
}
