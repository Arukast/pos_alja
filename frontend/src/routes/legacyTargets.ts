import { roleHome } from '../auth/roleHome';

const tabRoutes: Record<string, Record<string, string>> = {
    owner: {
        revenue: '/owner/revenue',
        cost: '/owner/base-cost',
        profit: '/owner/net-profit',
        expenses: '/owner/expenses',
        audit: '/owner/audit',
        users: '/owner/users',
        branches: '/owner/branches',
        materials: '/owner/materials',
        categories: '/owner/categories',
        products: '/owner/products',
        attributes: '/owner/attributes',
    },
    manager: {
        revenue: '/manager/revenue',
        cost: '/manager/base-cost',
        profit: '/manager/net-profit',
        expenses: '/manager/expenses',
        materials: '/manager/materials',
        ledgers: '/manager/ledgers',
        finishing: '/manager/finishing',
        pricing: '/manager/quantity-tiers',
        quantity_tiers: '/manager/quantity-tiers',
        leaderboard: '/manager/leaderboard',
        users: '/manager/users',
        categories: '/manager/categories',
        products: '/manager/products',
    },
    cashier: {
        payments: '/cashier/payments',
        pickup: '/cashier/pickup',
        expenses: '/cashier/expenses',
    },
    designer: {
        new_order: '/designer/new-order',
        order_list: '/designer/orders',
    },
    production: {
        print_queue: '/production/queue',
        waste: '/production/waste',
    },
};

export function legacyTarget(role: string, tab: string): string {
    return tabRoutes[role]?.[tab] ?? roleHome(role);
}
