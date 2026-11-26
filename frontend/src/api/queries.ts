import { apiFetch } from '../lib/api';
import type {
    ActivityLog,
    Attribute,
    AttributeType,
    BaseCostReport,
    Branch,
    Category,
    Customer,
    DashboardPayload,
    Expense,
    ExpenseReport,
    GrossRevenueReport,
    Invoice,
    LeaderboardReport,
    Material,
    NetProfitReport,
    Paginated,
    Payment,
    Pricing,
    Product,
    ProductionQueueItem,
    Purchase,
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

export interface QuoteOptions {
    categories: Category[];
    products: Product[];
    materials: Array<{ id: string; name: string; type: string | null; dimensions: string | null }>;
    attributes: Attribute[];
}

export interface Quote {
    pricing_id: string;
    unit_price: string;
    area: number;
    quantity: number;
    subtotal: string;
}

type MutationBody = object | undefined;

function mutate<T>(path: string, method: string, body?: MutationBody): Promise<T> {
    return apiFetch<T>(path, {
        method,
        body: body === undefined ? undefined : JSON.stringify(body),
    });
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

    materials: <T = Material>(params: QueryParams = {}) =>
        apiFetch<Paginated<T>>(`/materials${queryString(params)}`),

    pricing: (params: QueryParams = {}) =>
        apiFetch<Paginated<Pricing>>(`/pricing${queryString(params)}`),

    stockLedgers: (params: QueryParams = {}) =>
        apiFetch<Paginated<StockLedger>>(`/stock-ledgers${queryString(params)}`),

    purchases: (params: QueryParams = {}) =>
        apiFetch<Paginated<Purchase>>(`/purchases${queryString(params)}`),

    invoices: (params: QueryParams = {}) =>
        apiFetch<Paginated<Invoice>>(`/invoices${queryString(params)}`),

    invoice: (id: string) => apiFetch<{ invoice: Invoice }>(`/invoices/${id}`),

    ticket: (id: string) => apiFetch<{ invoice: Invoice }>(`/invoices/${id}/ticket`),

    receipt: (id: string) => apiFetch<{ invoice: Invoice }>(`/invoices/${id}/receipt`),

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

    quoteOptions: () => apiFetch<QuoteOptions>('/quotes/options'),

    quote: (data: MutationBody) => mutate<{ quote: Quote }>('/quotes', 'POST', data),

    customers: (params: QueryParams = {}) =>
        apiFetch<{ data: Customer[] }>(`/customers${queryString(params)}`),

    createCustomer: (data: MutationBody) =>
        mutate<{ customer: Customer }>('/customers', 'POST', data),

    createInvoice: (data: MutationBody) =>
        mutate<{ invoice: Invoice }>('/invoices', 'POST', data),

    updateInvoiceItems: (invoiceId: string, data: MutationBody) =>
        mutate<{ invoice: Invoice }>(`/invoices/${invoiceId}/items`, 'PUT', data),

    downPayment: (invoiceId: string, data: MutationBody) =>
        mutate<{ payment: Payment; invoice: Invoice }>(
            `/invoices/${invoiceId}/down-payments`,
            'POST',
            data
        ),

    settle: (invoiceId: string, data: MutationBody) =>
        mutate<{ payment: Payment | null; invoice: Invoice }>(
            `/invoices/${invoiceId}/settlements`,
            'POST',
            data
        ),

    pickup: (invoiceId: string) =>
        mutate<{ invoice: Invoice }>(`/invoices/${invoiceId}/pickup`, 'POST'),

    createExpense: (data: MutationBody) =>
        mutate<{ expense: Expense }>('/expenses', 'POST', data),

    createPurchase: (data: MutationBody) =>
        mutate<{ purchase: Purchase }>('/purchases', 'POST', data),

    adjustMaterialStock: (id: string, data: MutationBody) =>
        mutate<{ material: Material }>(`/materials/${id}/adjustments`, 'POST', data),

    startOrder: (orderId: string) =>
        mutate<{ order: ProductionQueueItem }>(`/orders/${orderId}/start`, 'POST'),

    completeOrder: (orderId: string) =>
        mutate<{ order: ProductionQueueItem }>(`/orders/${orderId}/complete`, 'POST'),

    failOrder: (orderId: string, data: { note: string }) =>
        mutate<{ order: ProductionQueueItem }>(`/orders/${orderId}/fail`, 'POST', data),

    reopenOrder: (orderId: string) =>
        mutate<{ order: ProductionQueueItem }>(`/orders/${orderId}/reopen`, 'POST'),

    wasteOptions: () =>
        apiFetch<{
            materials: Array<{ id: string; name: string; type: string | null; dimensions: string | null; unit: string }>;
            designers: Array<{ id: string; name: string }>;
        }>('/waste/options'),

    createWaste: (data: MutationBody) =>
        mutate<{ waste: Waste }>('/waste', 'POST', data),

    admin: {
        createBranch: (data: MutationBody) =>
            mutate<{ branch: Branch }>('/branches', 'POST', data),
        updateBranch: (id: string, data: MutationBody) =>
            mutate<{ branch: Branch }>(`/branches/${id}`, 'PUT', data),
        toggleBranch: (id: string) =>
            mutate<{ branch: Branch }>(`/branches/${id}/status`, 'PATCH'),
        deleteBranch: (id: string) => mutate<void>(`/branches/${id}`, 'DELETE'),

        createUser: (data: MutationBody) =>
            mutate<{ user: UserRecord }>('/users', 'POST', data),
        updateUser: (id: string, data: MutationBody) =>
            mutate<{ user: UserRecord }>(`/users/${id}`, 'PUT', data),
        deleteUser: (id: string) => mutate<void>(`/users/${id}`, 'DELETE'),
        restoreUser: (id: string) =>
            mutate<{ user: UserRecord }>(`/users/${id}/restore`, 'POST'),

        similarMaterials: (name: string) =>
            apiFetch<{ data: Material[] }>(
                `/materials/similar${queryString({ name })}`
            ),
        createMaterial: (data: MutationBody) =>
            mutate<{ material: Material }>('/materials', 'POST', data),
        updateMaterial: (id: string, data: MutationBody) =>
            mutate<{ material: Material }>(`/materials/${id}`, 'PUT', data),
        deleteMaterial: (id: string) => mutate<void>(`/materials/${id}`, 'DELETE'),
        restoreMaterial: (id: string) =>
            mutate<{ material: Material }>(`/materials/${id}/restore`, 'POST'),
        restockMaterial: (id: string, data: MutationBody) =>
            mutate<{ material: Material }>(`/materials/${id}/stock`, 'POST', data),
        activateMaterial: (id: string, data: MutationBody) =>
            mutate<{ material: Material }>(`/materials/${id}/activate`, 'POST', data),

        createCategory: (data: MutationBody) =>
            mutate<{ category: Category }>('/categories', 'POST', data),
        updateCategory: (id: string, data: MutationBody) =>
            mutate<{ category: Category }>(`/categories/${id}`, 'PUT', data),
        deleteCategory: (id: string) => mutate<void>(`/categories/${id}`, 'DELETE'),

        createProduct: (data: MutationBody) =>
            mutate<{ product: Product }>('/products', 'POST', data),
        updateProduct: (id: string, data: MutationBody) =>
            mutate<{ product: Product }>(`/products/${id}`, 'PUT', data),
        deleteProduct: (id: string) => mutate<void>(`/products/${id}`, 'DELETE'),
        restoreProduct: (id: string) =>
            mutate<{ product: Product }>(`/products/${id}/restore`, 'POST'),

        createAttributeType: (data: MutationBody) =>
            mutate<{ attribute_type: AttributeType }>('/attribute-types', 'POST', data),
        deleteAttributeType: (id: string) =>
            mutate<void>(`/attribute-types/${id}`, 'DELETE'),

        createAttribute: (data: MutationBody) =>
            mutate<{ attribute: Attribute }>('/attributes', 'POST', data),
        updateAttribute: (id: string, data: MutationBody) =>
            mutate<{ attribute: Attribute }>(`/attributes/${id}`, 'PUT', data),
        deleteAttribute: (id: string) => mutate<void>(`/attributes/${id}`, 'DELETE'),

        createPricing: (data: MutationBody) =>
            mutate<{ pricing: Pricing }>('/pricing', 'POST', data),
        updatePricing: (id: string, data: MutationBody) =>
            mutate<{ pricing: Pricing }>(`/pricing/${id}`, 'PUT', data),
        deletePricing: (id: string) => mutate<void>(`/pricing/${id}`, 'DELETE'),
        clonePricing: (data: MutationBody) =>
            mutate<{ cloned: number }>('/pricing/clone-tiers', 'POST', data),
    },
};
