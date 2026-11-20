import { apiFetch } from '../lib/api';
import type {
    ActivityLog,
    Attribute,
    AttributeType,
    BaseCostReport,
    Branch,
    Category,
    DashboardPayload,
    Expense,
    ExpenseReport,
    GrossRevenueReport,
    Invoice,
    LeaderboardReport,
    Machine,
    Material,
    NetProfitReport,
    Paginated,
    Pricing,
    Product,
    ProductionQueueItem,
    StockLedger,
    UserRecord,
    Waste,
} from './types';

type QueryValue = string | number | boolean | null | undefined;
type QueryParams = Record<string, QueryValue>;

function queryString(params: object = {}): string {
    const search = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') {
            search.set(key, String(value));
        }
    }

    const query = search.toString();

    return query ? `?${query}` : '';
}

export interface ReportParams {
    branch_id?: string;
    start_date: string;
    end_date: string;
}

export interface PageParams {
    page?: number;
    search?: string;
}

export const api = {
    dashboard: (params: QueryParams = {}) =>
        apiFetch<DashboardPayload>(`/dashboard${queryString(params)}`),

    branches: (params: QueryParams = {}) =>
        apiFetch<Paginated<Branch>>(`/branches${queryString(params)}`),

    users: (params: QueryParams = {}) =>
        apiFetch<Paginated<UserRecord>>(`/users${queryString(params)}`),

    categories: () => apiFetch<{ data: Category[] }>('/categories'),

    attributeTypes: () => apiFetch<{ data: AttributeType[] }>('/attribute-types'),

    attributes: (params: QueryParams = {}) =>
        apiFetch<{ data: Attribute[] }>(`/attributes${queryString(params)}`),

    products: (params: QueryParams = {}) =>
        apiFetch<Paginated<Product>>(`/products${queryString(params)}`),

    materials: (params: QueryParams = {}) =>
        apiFetch<Paginated<Material>>(`/materials${queryString(params)}`),

    machines: (params: QueryParams = {}) =>
        apiFetch<Paginated<Machine>>(`/machines${queryString(params)}`),

    pricing: (params: QueryParams = {}) =>
        apiFetch<Paginated<Pricing>>(`/pricing${queryString(params)}`),

    stockLedgers: (params: QueryParams = {}) =>
        apiFetch<Paginated<StockLedger>>(`/stock-ledgers${queryString(params)}`),

    invoices: (params: QueryParams = {}) =>
        apiFetch<Paginated<Invoice>>(`/invoices${queryString(params)}`),

    invoice: (id: string) => apiFetch<{ invoice: Invoice }>(`/invoices/${id}`),

    expenses: (params: QueryParams = {}) =>
        apiFetch<Paginated<Expense>>(`/expenses${queryString(params)}`),

    productionQueue: (params: QueryParams = {}) =>
        apiFetch<Paginated<ProductionQueueItem>>(`/production/queue${queryString(params)}`),

    waste: (params: QueryParams = {}) =>
        apiFetch<Paginated<Waste>>(`/waste${queryString(params)}`),

    auditLogs: (params: QueryParams = {}) =>
        apiFetch<Paginated<ActivityLog>>(`/audit-logs${queryString(params)}`),

    report: {
        grossRevenue: (params: ReportParams) =>
            apiFetch<GrossRevenueReport>(`/reports/gross-revenue${queryString(params)}`),

        baseCost: (params: ReportParams) =>
            apiFetch<BaseCostReport>(`/reports/base-cost${queryString(params)}`),

        netProfit: (params: ReportParams) =>
            apiFetch<NetProfitReport>(`/reports/net-profit${queryString(params)}`),

        expenses: (params: ReportParams) =>
            apiFetch<ExpenseReport>(`/reports/expenses${queryString(params)}`),

        sparkline: (params: QueryParams = {}) =>
            apiFetch<{ points: number[]; labels: string[] }>(
                `/reports/sparkline${queryString(params)}`
            ),

        leaderboard: () => apiFetch<LeaderboardReport>('/reports/designer-leaderboard'),
    },
};
