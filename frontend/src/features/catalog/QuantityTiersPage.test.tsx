import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ComponentType } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';

import { AuthProvider } from '../../auth/AuthProvider';
import type { ProductQuantityDiscount } from '../../api/types';
import { OwnerQuantityTiersPage } from '../owner/AdminPages';
import { ManagerQuantityTiersPage } from '../manager/AdminPages';

const tier: ProductQuantityDiscount = {
    id: 'tier-1',
    product_id: 'p-banner',
    product: { id: 'p-banner', name: 'Spanduk Flexy' },
    min_qty: 100,
    discount_percent: '10.00',
};

// No jsdom or Testing Library in this repo: server markup is enough to prove the
// read-only manager view renders the tier columns without the create button.
function renderPage(Page: ComponentType, prefix: string, rows: ProductQuantityDiscount[] = []): string {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    queryClient.setQueryData([prefix, 'quantity-tiers', 1], {
        data: rows,
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    });

    return renderToStaticMarkup(
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <Page />
            </AuthProvider>
        </QueryClientProvider>
    );
}

describe('quantity tiers pages', () => {
    it('shows tier columns to a manager without a create button', () => {
        const html = renderPage(ManagerQuantityTiersPage, 'manager');

        expect(html).toContain('Jml min');
        expect(html).toContain('Diskon');
        expect(html).not.toContain('>Tambah tier</button>');
    });

    it('names the read-only tier card and explains who can edit it', () => {
        const html = renderPage(ManagerQuantityTiersPage, 'manager');

        expect(html).toContain('Tier Jumlah');
        expect(html).toContain('Hanya pemilik dapat mengubah tier.');
    });

    it('shows the create button to the owner', () => {
        const html = renderPage(OwnerQuantityTiersPage, 'owner');

        expect(html).toContain('>Tambah tier</button>');
    });

    it('labels each tier row with its product and minimum quantity', () => {
        const html = renderPage(OwnerQuantityTiersPage, 'owner', [tier]);

        expect(html).toContain('aria-label="Ubah Spanduk Flexy 100"');
    });

    it('offers more than ten active products in the tier product select', () => {
        const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
        queryClient.setQueryData(['owner', 'quantity-tiers', 1], {
            data: [],
            links: { first: null, last: null, prev: null, next: null },
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        });
        queryClient.setQueryData(['products', 'options'], {
            data: Array.from({ length: 12 }, (_, index) => ({
                id: `p-${index + 1}`,
                name: `Produk ${index + 1}`,
            })),
        });

        const html = renderToStaticMarkup(
            <QueryClientProvider client={queryClient}>
                <AuthProvider>
                    <OwnerQuantityTiersPage />
                </AuthProvider>
            </QueryClientProvider>
        );

        expect(html).toContain('<option value="p-11">Produk 11</option>');
        expect(html).toContain('<option value="p-12">Produk 12</option>');
    });
});
