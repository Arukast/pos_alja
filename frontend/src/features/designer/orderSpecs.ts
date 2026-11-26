import type { Order } from '../../api/types';

export type OrderSpecs = Pick<Order, 'length' | 'area' | 'pricing'>;

/** A legacy linear order without length or area cannot be repriced without guessing. */
export function hasMissingLinearLength(order: OrderSpecs): boolean {
    return order.pricing?.calculation_archetype === 'linear_dimension'
        && order.length === null
        && Number(order.area) <= 0;
}
