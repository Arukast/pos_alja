import { createBrowserRouter } from 'react-router';

import { RequireAuth, RequireRole, RoleRedirect } from '../auth/RequireAuth';
import { AppShell } from '../components/AppShell';
import { CashierDashboardPage } from '../features/cashier/CashierDashboardPage';
import { DesignerDashboardPage } from '../features/designer/DesignerDashboardPage';
import { DesignerNewOrderPage } from '../features/designer/DesignerNewOrderPage';
import { DesignerOrdersPage } from '../features/designer/DesignerOrdersPage';
import { NotFoundPage } from '../features/errors/NotFoundPage';
import {
    BranchesPage,
    OwnerCategoriesPage,
    OwnerMachinesPage,
    OwnerMaterialsPage,
    OwnerUsersPage,
} from '../features/owner/AdminPages';
import { AuditPage } from '../features/owner/AuditPage';
import { BaseCostPage } from '../features/owner/BaseCostPage';
import { ExpensesPage } from '../features/owner/ExpensesPage';
import { GrossRevenuePage } from '../features/owner/GrossRevenuePage';
import { NetProfitPage } from '../features/owner/NetProfitPage';
import { OwnerDashboardPage } from '../features/owner/OwnerDashboardPage';
import {
    ManagerCategoriesPage,
    ManagerFinishingPage,
    ManagerLeaderboardPage,
    ManagerLedgersPage,
    ManagerMachinesPage,
    ManagerMaterialsPage,
    ManagerPricingPage,
    ManagerUsersPage,
} from '../features/manager/AdminPages';
import { ManagerDashboardPage } from '../features/manager/ManagerDashboardPage';
import { ProfilePage } from '../features/profile/ProfilePage';
import { ProductionDashboardPage } from '../features/production/ProductionDashboardPage';
import { LoginPage } from '../features/auth/LoginPage';
import { LegacyDashboardRedirect } from './legacy';

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
                            { index: true, element: <OwnerDashboardPage /> },
                            { path: 'revenue', element: <GrossRevenuePage /> },
                            { path: 'base-cost', element: <BaseCostPage /> },
                            { path: 'net-profit', element: <NetProfitPage /> },
                            { path: 'expenses', element: <ExpensesPage /> },
                            { path: 'audit', element: <AuditPage /> },
                            { path: 'users', element: <OwnerUsersPage /> },
                            { path: 'branches', element: <BranchesPage /> },
                            { path: 'categories', element: <OwnerCategoriesPage /> },
                            { path: 'machines', element: <OwnerMachinesPage /> },
                            { path: 'materials', element: <OwnerMaterialsPage /> },
                        ],
                    },
                    {
                        path: 'manager',
                        element: <RequireRole roles={['manager']} />,
                        children: [
                            { index: true, element: <ManagerDashboardPage /> },
                            { path: 'materials', element: <ManagerMaterialsPage /> },
                            { path: 'ledgers', element: <ManagerLedgersPage /> },
                            { path: 'finishing', element: <ManagerFinishingPage /> },
                            { path: 'pricing', element: <ManagerPricingPage /> },
                            { path: 'leaderboard', element: <ManagerLeaderboardPage /> },
                            { path: 'users', element: <ManagerUsersPage /> },
                            { path: 'categories', element: <ManagerCategoriesPage /> },
                            { path: 'machines', element: <ManagerMachinesPage /> },
                        ],
                    },
                    {
                        path: 'cashier',
                        element: <RequireRole roles={['cashier']} />,
                        children: [
                            { index: true, element: <CashierDashboardPage /> },
                            { path: 'payments', element: <CashierDashboardPage /> },
                            { path: 'pickup', element: <CashierDashboardPage /> },
                            { path: 'expenses', element: <CashierDashboardPage /> },
                        ],
                    },
                    {
                        path: 'designer',
                        element: <RequireRole roles={['designer']} />,
                        children: [
                            { index: true, element: <DesignerDashboardPage /> },
                            { path: 'new-order', element: <DesignerNewOrderPage /> },
                            { path: 'orders', element: <DesignerOrdersPage /> },
                        ],
                    },
                    {
                        path: 'production',
                        element: <RequireRole roles={['production']} />,
                        children: [
                            { index: true, element: <ProductionDashboardPage /> },
                            { path: 'queue', element: <ProductionDashboardPage /> },
                            { path: 'waste', element: <ProductionDashboardPage /> },
                        ],
                    },
                ],
            },
        ],
    },
    { path: '*', element: <NotFoundPage /> },
]);
