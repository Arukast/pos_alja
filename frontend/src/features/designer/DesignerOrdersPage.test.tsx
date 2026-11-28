import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router';
import { describe, expect, it } from 'vitest';

import type { Invoice } from '../../api/types';
import { AuthProvider } from '../../auth/AuthProvider';
import { DesignerOrdersPage } from './DesignerOrdersPage';

const invoice: Invoice = {
    id: 'inv-1',
    invoice_number: 'INV-001',
    branch: null,
    customer: null,
    designer: null,
    created_date: '2026-11-27',
    deadline: null,
    status: 0,
    pickup_status: 0,
    pickup_date: null,
    source: 0,
    discount: '0.00',
    dp_amount: '0.00',
    accounts_receivable: '100000.00',
    grand_total: '100000.00',
    orders: [{
        id: 'order-1',
        product_id: 'p-banner',
        material_id: 'm-flexy',
        product: 'Spanduk Flexy',
        material: 'Flexy',
        customer_type: 'Reguler',
        discount_amount: '0.00',
        file_name: 'spanduk_toko.pdf',
        notes: null,
        area: '2.00',
        width: '2',
        length: '1',
        quantity: '1',
        selected_attributes: [],
        ordered_at: '2026-11-27',
        status: 0,
        pickup_status: 0,
        is_manual_price: false,
    }],
};

function renderOrdersPage(): string {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    queryClient.setQueryData(['invoice', 'detail', null], { invoice });

    return renderToStaticMarkup(
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <MemoryRouter initialEntries={['/designer/orders']}>
                    <DesignerOrdersPage />
                </MemoryRouter>
            </AuthProvider>
        </QueryClientProvider>
    );
}

describe('DesignerOrdersPage', () => {
    it('lets a long item name wrap so the detail table fits a 390px viewport', () => {
        const html = renderOrdersPage();

        expect(html).toContain('spanduk_toko.pdf');
        expect(html).toContain('[overflow-wrap:anywhere]');
    });

    it('keeps the item table wide enough to scroll instead of collapsing columns at 390px', () => {
        const html = renderOrdersPage();

        expect(html).toMatch(/<table[^>]*aria-label="Item pesanan"[^>]*class="[^"]*min-w-\[36rem\]!/);
    });
});
