import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter, Route, Routes } from 'react-router';
import { describe, expect, it } from 'vitest';

import type { QuoteOptions } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { AuthProvider } from '../../auth/AuthProvider';
import { DesignerEditOrderPage } from './DesignerEditOrderPage';

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
        material: 'Flexy',
        customer_type: 'Reguler',
        discount_amount: '0.00',
        file_name: 'banner.pdf',
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

const options: QuoteOptions = {
    categories: [],
    products: [{
        id: 'p-banner',
        name: 'Spanduk Flexy',
        selling_unit: 'm2',
        is_active: true,
        category: null,
        materials: [{ id: 'm-flexy', name: 'Flexy' }],
        material_ids: ['m-flexy'],
        requires_material: true,
        uses_attributes: false,
        attribute_types: [],
        attribute_type_ids: [],
        compatible_attributes: [],
        attribute_ids: [],
    }],
    materials: [{ id: 'm-flexy', name: 'Flexy', type: 'roll', dimensions: null }],
    attributes: [],
    attribute_types: [],
};

function renderEditPage(currentInvoice: Invoice = invoice, currentOptions: QuoteOptions = options): string {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    queryClient.setQueryData(['invoice', 'detail', 'inv-1'], { invoice: currentInvoice });
    queryClient.setQueryData(['quote-options', 'edit'], currentOptions);

    return renderToStaticMarkup(
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <MemoryRouter initialEntries={['/designer/orders/inv-1/edit']}>
                    <Routes>
                        <Route path="/designer/orders/:id/edit" element={<DesignerEditOrderPage />} />
                    </Routes>
                </MemoryRouter>
            </AuthProvider>
        </QueryClientProvider>
    );
}

describe('DesignerEditOrderPage', () => {
    it('offers a separate product select and material select for an editable invoice', () => {
        const html = renderEditPage();

        expect(html).toContain('>Produk<');
        expect(html).not.toContain('>Produk dan bahan<');
        expect(html).toContain('>Bahan<');
        expect(html).toMatch(/<option value="m-flexy"[^>]*>Flexy<\/option>/);
    });

    it('keeps a saved inactive material in the Bahan select', () => {
        const savedInvoice: Invoice = {
            ...invoice,
            orders: [{ ...(invoice.orders ?? [])[0], material_id: 'm-lama', material: 'Bahan Lama' }],
        };

        const html = renderEditPage(savedInvoice);

        expect(html).toMatch(/<option value="m-lama"[^>]*>Bahan Lama \(nonaktif\)<\/option>/);
    });

    it('keeps a saved material that is no longer compatible with the product', () => {
        const savedInvoice: Invoice = {
            ...invoice,
            orders: [{ ...(invoice.orders ?? [])[0], material_id: 'm-kertas', material: 'Kertas' }],
        };
        const compatibleOptions: QuoteOptions = {
            ...options,
            products: [{ ...options.products[0], material_ids: ['m-flexy'] }],
            materials: [...options.materials, { id: 'm-kertas', name: 'Kertas', type: null, dimensions: null }],
        };

        const html = renderEditPage(savedInvoice, compatibleOptions);

        expect(html).toMatch(/<option value="m-kertas"[^>]*>Kertas \(tidak kompatibel\)<\/option>/);
    });
});
