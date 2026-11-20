import { createBrowserRouter } from 'react-router';

import { RequireAuth, RequireRole, RoleRedirect } from '../auth/RequireAuth';
import { AppShell } from '../components/AppShell';
import { LoginPage } from '../features/auth/LoginPage';
import { PlaceholderPage } from '../features/dashboard/PlaceholderPage';
import { NotFoundPage } from '../features/errors/NotFoundPage';
import { ProfilePage } from '../features/profile/ProfilePage';
import { LegacyDashboardRedirect } from './legacy';

function page(title: string, description: string) {
    return <PlaceholderPage title={title} description={description} />;
}

export const router = createBrowserRouter([
    { path: '/login', element: <LoginPage /> },
    {
        element: <RequireAuth />,
        children: [
            {
                element: <AppShell />,
                children: [
                    { index: true, element: <RoleRedirect /> },
                    { path: 'profile', element: <ProfilePage /> },
                    { path: 'dashboard', element: <LegacyDashboardRedirect /> },
                    {
                        path: 'owner',
                        element: <RequireRole roles={['owner']} />,
                        children: [
                            {
                                index: true,
                                element: page(
                                    'Owner Dashboard',
                                    'Cross-branch finance, production status, and branch comparison.'
                                ),
                            },
                            {
                                path: 'revenue',
                                element: page(
                                    'Gross Revenue Detail',
                                    'Collected payments by method, branch, and period.'
                                ),
                            },
                            {
                                path: 'base-cost',
                                element: page(
                                    'Base Cost Detail',
                                    'Invoice HPP and material waste cost breakdowns.'
                                ),
                            },
                            {
                                path: 'net-profit',
                                element: page(
                                    'Net Profit Detail',
                                    'Revenue minus HPP and operating expenses.'
                                ),
                            },
                            {
                                path: 'expenses',
                                element: page(
                                    'Expenses Detail',
                                    'Branch operating expenses by category and period.'
                                ),
                            },
                            {
                                path: 'audit',
                                element: page(
                                    'Audit Trail',
                                    'Searchable record of important system changes.'
                                ),
                            },
                            {
                                path: 'users',
                                element: page(
                                    'User Management',
                                    'Owner-wide staff accounts, roles, and deactivation.'
                                ),
                            },
                            {
                                path: 'branches',
                                element: page(
                                    'Branch Management',
                                    'Branch records, settings, and active status.'
                                ),
                            },
                            {
                                path: 'categories',
                                element: page(
                                    'Product Categories',
                                    'Category archetypes and allowed attribute types.'
                                ),
                            },
                            {
                                path: 'machines',
                                element: page(
                                    'Printer Machines',
                                    'Machine records and base cost per branch.'
                                ),
                            },
                            {
                                path: 'materials',
                                element: page(
                                    'Materials & Stock',
                                    'Global materials, branch stock, and restocking.'
                                ),
                            },
                        ],
                    },
                    {
                        path: 'manager',
                        element: <RequireRole roles={['manager']} />,
                        children: [
                            {
                                index: true,
                                element: page(
                                    'Branch Manager Dashboard',
                                    'Inventory, pricing, staff, and monthly performance for your branch.'
                                ),
                            },
                            {
                                path: 'materials',
                                element: page(
                                    'Materials & Stock',
                                    'Branch inventory levels, restocking, and activation.'
                                ),
                            },
                            {
                                path: 'ledgers',
                                element: page(
                                    'Stock Ledgers',
                                    'Stock movement history and recent branch expenses.'
                                ),
                            },
                            {
                                path: 'finishing',
                                element: page(
                                    'Display & Finishing',
                                    'Manage finishing, cut, and display attributes.'
                                ),
                            },
                            {
                                path: 'pricing',
                                element: page(
                                    'Pricing Matrices',
                                    'Tiered pricing rows and modifier rules.'
                                ),
                            },
                            {
                                path: 'leaderboard',
                                element: page(
                                    'Designer Leaderboard',
                                    'Designer revenue, order count, AOV, and waste accuracy.'
                                ),
                            },
                            {
                                path: 'users',
                                element: page(
                                    'User Management',
                                    'Branch staff accounts and role assignments.'
                                ),
                            },
                            {
                                path: 'categories',
                                element: page(
                                    'Product Categories',
                                    'Category archetypes and allowed attribute types.'
                                ),
                            },
                            {
                                path: 'machines',
                                element: page(
                                    'Printer Machines',
                                    'Machine records and base cost for your branch.'
                                ),
                            },
                        ],
                    },
                    {
                        path: 'cashier',
                        element: <RequireRole roles={['cashier']} />,
                        children: [
                            {
                                index: true,
                                element: page(
                                    'Cashier Dashboard',
                                    'Checkout queue, down payments, settlements, and pickup.'
                                ),
                            },
                            {
                                path: 'payments',
                                element: page(
                                    'Payment Queue',
                                    'Unpaid and down-payment invoices awaiting checkout.'
                                ),
                            },
                            {
                                path: 'pickup',
                                element: page(
                                    'Pickup Queue',
                                    'Paid orders waiting for customer pickup.'
                                ),
                            },
                            {
                                path: 'expenses',
                                element: page(
                                    'Expenses',
                                    'Daily branch expenses and drawer float records.'
                                ),
                            },
                        ],
                    },
                    {
                        path: 'designer',
                        element: <RequireRole roles={['designer']} />,
                        children: [
                            {
                                index: true,
                                element: page(
                                    'Designer Dashboard',
                                    'Today’s intake, draft cart, and recent invoices.'
                                ),
                            },
                            {
                                path: 'new-order',
                                element: page(
                                    'New Order',
                                    'Customer intake, specifications, live quote, and cart.'
                                ),
                            },
                            {
                                path: 'orders',
                                element: page(
                                    'Order List',
                                    'Search and paginate invoices from your branch.'
                                ),
                            },
                        ],
                    },
                    {
                        path: 'production',
                        element: <RequireRole roles={['production']} />,
                        children: [
                            {
                                index: true,
                                element: page(
                                    'Production Dashboard',
                                    'Queue, active prints, completed jobs, and waste.'
                                ),
                            },
                            {
                                path: 'queue',
                                element: page(
                                    'Print Queue',
                                    'Orders waiting to start or currently printing.'
                                ),
                            },
                            {
                                path: 'waste',
                                element: page(
                                    'Waste Logging',
                                    'Failed prints and material usage deductions.'
                                ),
                            },
                        ],
                    },
                ],
            },
        ],
    },
    { path: '*', element: <NotFoundPage /> },
]);
