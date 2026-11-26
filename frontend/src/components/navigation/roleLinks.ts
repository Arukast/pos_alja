import {
    Box,
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
            label: 'Ringkasan',
            links: [{ to: '/owner', label: 'Dasbor', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Analitik',
            links: [
                { to: '/owner/revenue', label: 'Rincian Pendapatan Bruto', icon: TrendingUp },
                { to: '/owner/base-cost', label: 'Rincian Biaya Dasar', icon: Wallet },
                { to: '/owner/net-profit', label: 'Rincian Laba Bersih', icon: PiggyBank },
                { to: '/owner/expenses', label: 'Rincian Pengeluaran', icon: Receipt },
                { to: '/owner/audit', label: 'Jejak Audit', icon: ClipboardList },
            ],
        },
        {
            label: 'Administrasi',
            links: [
                { to: '/owner/users', label: 'Manajemen Pengguna', icon: Users },
                { to: '/owner/branches', label: 'Manajemen Cabang', icon: Building2 },
                { to: '/owner/categories', label: 'Kategori Produk', icon: Tags },
                { to: '/owner/products', label: 'Produk', icon: Box },
                { to: '/owner/materials', label: 'Bahan & Stok', icon: Package },
                { to: '/owner/purchases', label: 'Pembelian', icon: ShoppingCart },
            ],
        },
    ],
    manager: [
        {
            label: 'Ringkasan',
            links: [
                { to: '/manager', label: 'Dasbor', icon: LayoutDashboard, end: true },
                { to: '/manager/leaderboard', label: 'Papan Peringkat Desainer', icon: TrendingUp },
            ],
        },
        {
            label: 'Inventaris',
            links: [
                { to: '/manager/materials', label: 'Bahan & Stok', icon: Package },
                { to: '/manager/purchases', label: 'Pembelian', icon: ShoppingCart },
                { to: '/manager/ledgers', label: 'Buku Stok', icon: ListOrdered },
            ],
        },
        {
            label: 'Konfigurasi',
            links: [
                { to: '/manager/finishing', label: 'Display & Finishing', icon: Tags },
                { to: '/manager/categories', label: 'Kategori Produk', icon: Tags },
                { to: '/manager/products', label: 'Produk', icon: Box },
                { to: '/manager/pricing', label: 'Matriks Harga', icon: CircleDollarSign },
            ],
        },
        {
            label: 'Administrasi',
            links: [{ to: '/manager/users', label: 'Manajemen Pengguna', icon: Users }],
        },
    ],
    cashier: [
        {
            label: 'Ringkasan',
            links: [{ to: '/cashier', label: 'Dasbor', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Operasional',
            links: [
                { to: '/cashier/payments', label: 'Antrean Pembayaran', icon: CreditCard },
                { to: '/cashier/pickup', label: 'Antrean Pengambilan', icon: Truck },
            ],
        },
    ],
    designer: [
        {
            label: 'Ringkasan',
            links: [{ to: '/designer', label: 'Dasbor', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Pesanan',
            links: [
                { to: '/designer/new-order', label: 'Pesanan Baru', icon: ShoppingCart },
                { to: '/designer/orders', label: 'Daftar Pesanan', icon: ClipboardList },
            ],
        },
    ],
    production: [
        {
            label: 'Ringkasan',
            links: [{ to: '/production', label: 'Dasbor', icon: LayoutDashboard, end: true }],
        },
        {
            label: 'Operasional',
            links: [
                { to: '/production/materials', label: 'Bahan & Stok', icon: Package },
                { to: '/production/queue', label: 'Antrean Cetak', icon: Printer },
                { to: '/production/waste', label: 'Riwayat Bahan Terbuang', icon: Recycle },
            ],
        },
    ],
};
