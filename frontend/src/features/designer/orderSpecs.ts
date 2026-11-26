import type { Order } from '../../api/types';

export type OrderSpecs = Pick<Order, 'length' | 'area' | 'pricing'>;

/** Pricing types are now `unit` or `area`; linear dimension orders are no longer configured. */
export function hasMissingLinearLength(_order: OrderSpecs): boolean {
    return false;
}
