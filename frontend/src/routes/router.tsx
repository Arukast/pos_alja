import { createBrowserRouter, Navigate } from 'react-router';

import { RequireAuth, RequireRole, RoleRedirect } from '../auth/RequireAuth';
import { AppShell } from '../components/AppShell';
import { CashierDashboardPage } from '../features/cashier/CashierDashboardPage';
import { CashierExpensesPage } from '../features/cashier/CashierExpensesPage';
import { CashierPaymentsPage } from '../features/cashier/CashierPaymentsPage';
import { CashierPickupPage } from '../features/cashier/CashierPickupPage';
import { DesignerDashboardPage } from '../features/designer/DesignerDashboardPage';
import { DesignerEditOrderPage } from '../features/designer/DesignerEditOrderPage';
import { CustomerManagementPage } from '../features/customers/CustomerManagementPage';
import { DesignerNewOrderPage } from '../features/designer/DesignerNewOrderPage';
import { DesignerOrdersPage } from '../features/designer/DesignerOrdersPage';
import { NotFoundPage } from '../features/errors/NotFoundPage';
import {
    BranchesPage,
    OwnerAttributesPage,
    OwnerCategoriesPage,
    OwnerMaterialsPage,
    OwnerProductsPage,
    OwnerUsersPage,
} from '../features/owner/AdminPages';
import { AuditPage } from '../features/owner/AuditPage';
import { BackupPage } from '../features/owner/BackupPage';
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
    ManagerMaterialsPage,
    ManagerProductsPage,
    ManagerUsersPage,
} from '../features/manager/AdminPages';
import { ManagerDashboardPage } from '../features/manager/ManagerDashboardPage';
import { ProfilePage } from '../features/profile/ProfilePage';
import { ProductionDashboardPage } from '../features/production/ProductionDashboardPage';
import { ProductionMaterialsPage } from '../features/production/ProductionMaterialsPage';
import { ProductionQueuePage } from '../features/production/ProductionQueuePage';
import { ProductionWastePage } from '../features/production/ProductionWastePage';
import { PurchasesPage } from '../features/purchases/PurchasesPage';
import { ReceiptPage } from '../features/print/ReceiptPage';
import { TicketPage } from '../features/print/TicketPage';
import { LoginPage } from '../features/auth/LoginPage';
import { LegacyDashboardRedirect } from './legacy';

export const router = createBrowserRouter([
    { path: '/login', element: <LoginPage /> },
    {
        element: <RequireAuth />,
        children: [
            { path: 'orders/:id/ticket', element: <TicketPage /> },
            {
                path: 'orders/:id/receipt',
                element: <RequireRole roles={['owner', 'manager', 'cashier', 'designer']} />,
                children: [{ index: true, element: <ReceiptPage /> }],
            },
            {
                element: <AppShell />,
                children: [
                    { index: true, element: <RoleRedirect /> },
                    { path: 'profile', element: <ProfilePage /> },
                    { path: 'dashboard', element: <LegacyDashboardRedirect /> },
                    {
                        path: 'customers',
                        element: (
                            <RequireRole roles={['owner', 'manager', 'designer', 'cashier']} />
                        ),
                        children: [{ index: true, element: <CustomerManagementPage /> }],
                    },
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
                            { path: 'backups', element: <BackupPage /> },
                            { path: 'users', element: <OwnerUsersPage /> },
                            { path: 'branches', element: <BranchesPage /> },
                            { path: 'categories', element: <OwnerCategoriesPage /> },
                            { path: 'products', element: <OwnerProductsPage /> },
                            { path: 'attributes', element: <OwnerAttributesPage /> },
                            { path: 'materials', element: <OwnerMaterialsPage /> },
                            {
                                path: 'quantity-tiers',
                                element: <Navigate to="/owner/products" replace />,
                            },
                            { path: 'purchases', element: <PurchasesPage /> },
                        ],
                    },
                    {
                        path: 'manager',
                        element: <RequireRole roles={['manager']} />,
                        children: [
                            { index: true, element: <ManagerDashboardPage /> },
                            { path: 'revenue', element: <GrossRevenuePage /> },
                            { path: 'base-cost', element: <BaseCostPage /> },
                            { path: 'net-profit', element: <NetProfitPage /> },
                            { path: 'expenses', element: <ExpensesPage /> },
                            { path: 'materials', element: <ManagerMaterialsPage /> },
                            { path: 'purchases', element: <PurchasesPage /> },
                            { path: 'ledgers', element: <ManagerLedgersPage /> },
                            { path: 'finishing', element: <ManagerFinishingPage /> },
                            {
                                path: 'pricing',
                                element: <Navigate to="/manager/products" replace />,
                            },
                            {
                                path: 'quantity-tiers',
                                element: <Navigate to="/manager/products" replace />,
                            },
                            { path: 'leaderboard', element: <ManagerLeaderboardPage /> },
                            { path: 'users', element: <ManagerUsersPage /> },
                            { path: 'categories', element: <ManagerCategoriesPage /> },
                            { path: 'products', element: <ManagerProductsPage /> },
                        ],
                    },
                    {
                        path: 'cashier',
                        element: <RequireRole roles={['cashier']} />,
                        children: [
                            { index: true, element: <CashierDashboardPage /> },
                            { path: 'payments', element: <CashierPaymentsPage /> },
                            { path: 'pickup', element: <CashierPickupPage /> },
                            { path: 'expenses', element: <CashierExpensesPage /> },
                        ],
                    },
                    {
                        path: 'designer',
                        element: <RequireRole roles={['designer']} />,
                        children: [
                            { index: true, element: <DesignerDashboardPage /> },
                            { path: 'new-order', element: <DesignerNewOrderPage /> },
                            { path: 'orders', element: <DesignerOrdersPage /> },
                            { path: 'orders/:id/edit', element: <DesignerEditOrderPage /> },
                        ],
                    },
                    {
                        path: 'production',
                        element: <RequireRole roles={['production']} />,
                        children: [
                            { index: true, element: <ProductionDashboardPage /> },
                            { path: 'materials', element: <ProductionMaterialsPage /> },
                            { path: 'queue', element: <ProductionQueuePage /> },
                            { path: 'waste', element: <ProductionWastePage /> },
                        ],
                    },
                ],
            },
        ],
    },
    { path: '*', element: <NotFoundPage /> },
]);
