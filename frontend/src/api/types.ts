export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export interface CollectionResponse<T> {
    data: T[];
}

export interface Branch {
    id: string;
    name: string;
    code: string | null;
    address: string | null;
    phone: string | null;
    is_active: boolean;
    settings: Record<string, unknown> | null;
}

export interface UserRecord {
    id: string;
    name: string;
    username: string;
    email: string | null;
    email_verified_at: string | null;
    role: string;
    phone: string | null;
    address: string | null;
    branch: { id: string; name: string; code: string | null } | null;
}

export interface Customer {
    id: string;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    type: string | null;
}

export interface CategoryDiscountTier {
    id?: string;
    min_qty: number;
    discount_percent: string;
}

export interface Category {
    id: string;
    name: string;
    calculation_archetype: string;
    allowed_attribute_types: string[];
    discount_tiers: CategoryDiscountTier[];
}

export interface AttributeType {
    id: string;
    name: string;
    calculation_archetype: string;
}

export interface Attribute {
    id: string;
    name: string;
    type: string;
    branch_id: string | null;
    branch: { id: string; name: string } | null;
}

export interface Product {
    id: string;
    name: string;
    pricing_type: string;
    is_active: boolean;
    deleted_at?: string | null;
    category: { id: string; name: string; calculation_archetype: string } | null;
}

export interface MaterialStock {
    branch_id: string;
    branch_name: string | null;
    current_stock: string;
    min_stock: string;
    is_low_stock: boolean;
}

export interface Material {
    id: string;
    name: string;
    type: string | null;
    dimensions: string | null;
    unit: string;
    cost: string;
    price: string;
    category: { id: string; name: string } | null;
    current_stock: string | null;
    min_stock: string | null;
    is_low_stock: boolean | null;
    stocks: MaterialStock[];
}

export interface ProductionMaterial {
    id: string;
    name: string;
    type: string | null;
    dimensions: string | null;
    unit: string;
    category: { id: string; name: string } | null;
    current_stock: string | null;
    min_stock: string | null;
    is_low_stock: boolean | null;
}

export interface Pricing {
    id: string;
    branch_id: string | null;
    product: { id: string; name: string } | null;
    material: { id: string; name: string } | null;
    customer_type: string;
    min_qty: number;
    price: string;
    cost: string;
    profit: string;
}

export interface StockLedger {
    id: string;
    branch_id: string | null;
    branch: { id: string; name: string } | null;
    material: { id: string; name: string; unit: string } | null;
    type: string | null;
    reason: string | null;
    user: { id: string; name: string } | null;
    reference_type: string | null;
    reference_id: string | null;
    logged_at: string;
    initial_stock: string;
    added_stock: string;
    used_stock: string;
    wasted_stock: string;
    remaining_stock: string;
    notes: string | null;
}

export interface PurchaseItem {
    id: string;
    material: { id: string; name: string; unit: string } | null;
    quantity: string;
    unit_cost: string;
    subtotal: string;
}

export interface Purchase {
    id: string;
    branch: { id: string; name: string } | null;
    user: { id: string; name: string } | null;
    purchased_at: string;
    supplier: string | null;
    total_amount: string;
    notes: string | null;
    items: PurchaseItem[];
    created_at: string;
}

export interface Order {
    id: string;
    pricing_id: string | null;
    file_name: string | null;
    notes: string | null;
    area: string;
    quantity: string;
    unit_price?: string;
    subtotal?: string;
    cost?: string;
    profit?: string;
    ordered_at: string;
    status: number;
    pickup_status: number;
    is_manual_price: boolean;
}

export interface Payment {
    id: string;
    amount: string;
    payment_method: string;
    type: string;
    cashier_name: string;
    created_at: string;
}

export interface Invoice {
    id: string;
    invoice_number: string;
    branch: { id: string; name: string; address?: string | null; phone?: string | null } | null;
    customer: {
        id: string;
        name: string;
        phone: string | null;
        address?: string | null;
        email?: string | null;
        type?: string | null;
    } | null;
    designer: { id: string; name: string } | null;
    created_date: string;
    deadline: string | null;
    status: number;
    pickup_status: number;
    pickup_date: string | null;
    source: number;
    discount: string;
    dp_amount: string;
    accounts_receivable: string;
    grand_total: string;
    total_cost?: string;
    total_profit?: string;
    orders?: Order[];
    payments?: Payment[];
}

export interface Expense {
    id: string;
    branch_id: string | null;
    branch: { id: string; name: string } | null;
    incurred_at: string;
    amount: string;
    description: string | null;
    cashier_name: string | null;
    type: string;
    category: string;
}

export interface ProductionQueueItem {
    id: string;
    file_name: string | null;
    notes: string | null;
    area: string;
    quantity: string;
    ordered_at: string;
    status: number;
    pickup_status: number;
    has_waste: boolean;
    invoice: {
        id: string;
        invoice_number: string;
        deadline: string | null;
        pickup_status: number;
        customer: { id: string; name: string } | null;
        designer: { id: string; name: string } | null;
    } | null;
    operator: { id: string; name: string } | null;
}

export interface Waste {
    id: string;
    branch: { id: string; name: string } | null;
    material: { id: string; name: string; unit: string } | null;
    order_id: string | null;
    designer: { id: string; name: string } | null;
    operator: { id: string; name: string } | null;
    quantity: string;
    dimensions: string | null;
    notes: string | null;
    logged_at: string;
}

export interface ActivityLog {
    id: string;
    branch: { id: string; name: string } | null;
    user: { id: string; name: string } | null;
    username: string;
    role: string;
    action: string;
    description: string;
    model_type: string | null;
    model_id: string | null;
    payload: Record<string, unknown> | null;
    created_at: string;
}

export interface Sparkline {
    points: number[];
    labels: string[];
}

export interface Leader {
    id: string;
    name: string;
    order_count: number;
    revenue: string;
    average_order_value: string;
    waste_count: number;
}

export interface OwnerDashboard {
    role: 'owner';
    finance: {
        gross_revenue: string;
        total_cost: string;
        net_profit: string;
        expenses: string;
    };
    sparkline: Sparkline;
}

export interface ManagerDashboard {
    role: 'manager';
    counts: {
        materials: number;
        staff: number;
        pricing: number;
    };
    stock_ledgers: StockLedger[];
    expenses: Expense[];
    leaderboard: Leader[];
}

export interface CashierDashboard {
    role: 'cashier';
    stats: {
        awaiting_checkout: number;
        dp_collected_today: string;
        settlements_today: string;
        expenses_today: string;
    };
    billing_queue: Invoice[];
    pickup_queue: Invoice[];
    expenses: Expense[];
}

export interface DesignerDashboard {
    role: 'designer';
    stats: {
        invoices_today: number;
        grand_total_today: string;
    };
    recent_invoices: Invoice[];
}

export interface WasteByUnit {
    unit: string;
    quantity: number;
}

export interface ProductionDashboard {
    role: 'production';
    queued: number;
    processing: number;
    completed_today: number;
    waste_today_by_unit: WasteByUnit[];
    queue: ProductionQueueItem[];
}

export type DashboardPayload =
    | OwnerDashboard
    | ManagerDashboard
    | CashierDashboard
    | DesignerDashboard
    | ProductionDashboard;

export interface GrossRevenueReport {
    grand_total: string;
    cash_total: string;
    transfer_total: string;
    qris_total: string;
    payments: Array<{
        id: string;
        invoice_number: string | null;
        customer_name: string | null;
        branch_name: string | null;
        payment_method: string;
        amount: string;
        created_at: string;
    }>;
}

export interface BaseCostReport {
    total_hpp: string;
    invoice_cost_total: string;
    wasted_cost_total: string;
    invoices: Invoice[];
    wasted_materials: Array<{
        id: string;
        material: { id: string; name: string; unit: string; cost: string } | null;
        quantity: string;
        wasted_cost: string;
        logged_at: string;
        operator: string | null;
    }>;
}

export interface NetProfitReport {
    net_profit: string;
    gross_revenue: string;
    total_hpp: string;
    expenses_total: string;
    margin_rate: number;
    invoices: Invoice[];
    wasted_materials: BaseCostReport['wasted_materials'];
    expenses: Expense[];
}

export interface ExpenseReport {
    expenses_total: string;
    by_category: Record<string, string>;
    expenses: Expense[];
}

export interface LeaderboardReport {
    leaders: Leader[];
}
