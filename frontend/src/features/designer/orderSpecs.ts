import type { Order } from '../../api/types';

export type OrderSpecs = Pick<Order, 'length' | 'area' | 'material' | 'product_id' | 'material_id'>;

/** Orders now carry explicit product/material identity; linear dimension orders are no longer configured. */
export function hasMissingLinearLength(_order: OrderSpecs): boolean {
    return false;
}

/** Legacy orders without a linked product cannot be re-quoted safely. */
export function isOrderEditable(order: Order): boolean {
    return order.status === 0 && order.product_id !== null;
}
