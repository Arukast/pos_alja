import {
    Building2,
    CircleDollarSign,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    ListOrdered,
    Package,
    PiggyBank,
    Printer,
    Receipt,
    Recycle,
    ShoppingCart,
    Tags,
    TrendingUp,
    Truck,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';

export interface NavLinkItem {
    to: string;
    label: string;
    icon: LucideIcon;
    end?: boolean;
}

export interface NavGroup {
    label: string;
    links: NavLinkItem[];
}

export const roleLinks: Record<string, NavGroup[]> = {
    owner: [
        {
            label: 'Overview',
            links: [{ to: '/owner', label: 'Dashboard', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Insights',
            links: [
                { to: '/owner/revenue', label: 'Gross Revenue Detail', icon: TrendingUp },
                { to: '/owner/base-cost', label: 'Base Cost Detail', icon: Wallet },
                { to: '/owner/net-profit', label: 'Net Profit Detail', icon: PiggyBank },
                { to: '/owner/expenses', label: 'Expenses Detail', icon: Receipt },
                { to: '/owner/audit', label: 'Audit Trail', icon: ClipboardList },
            ],
        },
        {
            label: 'Administration',
            links: [
                { to: '/owner/users', label: 'User Management', icon: Users },
                { to: '/owner/branches', label: 'Branch Management', icon: Building2 },
                { to: '/owner/categories', label: 'Product Categories', icon: Tags },
                { to: '/owner/machines', label: 'Printer Machines', icon: Printer },
                { to: '/owner/materials', label: 'Materials & Stock', icon: Package },
            ],
        },
    ],
    manager: [
        {
            label: 'Overview',
            links: [
                { to: '/manager', label: 'Dashboard', icon: LayoutDashboard, end: true },
                { to: '/manager/leaderboard', label: 'Designer Leaderboard', icon: TrendingUp },
            ],
        },
        {
            label: 'Inventory',
            links: [
                { to: '/manager/materials', label: 'Materials & Stock', icon: Package },
                { to: '/manager/ledgers', label: 'Stock Ledgers', icon: ListOrdered },
            ],
        },
        {
            label: 'Configuration',
            links: [
                { to: '/manager/finishing', label: 'Display & Finishing', icon: Tags },
                { to: '/manager/categories', label: 'Product Categories', icon: Tags },
                { to: '/manager/machines', label: 'Printer Machines', icon: Printer },
                { to: '/manager/pricing', label: 'Pricing Matrices', icon: CircleDollarSign },
            ],
        },
        {
            label: 'Administration',
            links: [{ to: '/manager/users', label: 'User Management', icon: Users }],
        },
    ],
    cashier: [
        {
            label: 'Overview',
            links: [{ to: '/cashier', label: 'Dashboard', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Operations',
            links: [
                { to: '/cashier/payments', label: 'Payment Queue', icon: CreditCard },
                { to: '/cashier/pickup', label: 'Pickup Queue', icon: Truck },
                { to: '/cashier/expenses', label: 'Expenses', icon: Receipt },
            ],
        },
    ],
    designer: [
        {
            label: 'Overview',
            links: [{ to: '/designer', label: 'Dashboard', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Orders',
            links: [
                { to: '/designer/new-order', label: 'New Order', icon: ShoppingCart },
                { to: '/designer/orders', label: 'Order List', icon: ClipboardList },
            ],
        },
    ],
    production: [
        {
            label: 'Overview',
            links: [{ to: '/production', label: 'Dashboard', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Operations',
            links: [
                { to: '/production/queue', label: 'Print Queue', icon: Printer },
                { to: '/production/waste', label: 'Waste Logging', icon: Recycle },
            ],
        },
    ],
};
