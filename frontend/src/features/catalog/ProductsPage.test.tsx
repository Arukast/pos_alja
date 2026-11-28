import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { type ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

vi.mock('../../components/ui/Modal', () => ({
    Modal: ({ children }: { children: ReactNode }) => <div>{children}</div>,
}));

vi.mock('../../auth/useAuth', () => ({
    useAuth: () => ({ user: { role: 'manager' } }),
}));

import { ProductTiersField } from './ProductTiersField';
import { ManagerProductsPage } from '../manager/AdminPages';

describe('products page', () => {
    it('test_manager_product_modal_is_read_only_and_shows_tiers', () => {
        const tiers = [{ id: 't1', min_qty: 100, discount_percent: '15.00' }];
        const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
        client.setQueryData(['manager', 'products', '', '', '', 1], {
            data: [{
                id: 'p1', name: 'Spanduk', selling_unit: 'pcs', is_active: true,
                category: { id: 'c1', name: 'Banner', is_active: true },
                materials: [], material_ids: [], requires_material: false, uses_attributes: false,
                attribute_types: [], attribute_type_ids: [], compatible_attributes: [], attribute_ids: [],
                tiers,
            }],
            links: { first: null, last: null, prev: null, next: null },
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        const html = renderToStaticMarkup(
            <QueryClientProvider client={client}>
                <ManagerProductsPage />
            </QueryClientProvider>
        );

        expect(html).toContain('>Lihat</button>');
        expect(html).toContain('Tier Jumlah');
        expect(html).not.toContain('>Tambah produk</button>');
        expect(html).not.toContain('>Tambah tier</button>');
        expect(html).not.toContain('>Simpan</button>');
        expect(html).not.toContain('>Ubah</button>');
        expect(html).not.toContain('>Hapus</button>');
        expect(html).toContain('disabled');

        // Static markup cannot click Lihat; the read-only modal renders exactly this field.
        const modalTiers = renderToStaticMarkup(
            <ProductTiersField value={tiers} onChange={vi.fn()} errors={{}} disabled />
        );
        expect(modalTiers).toContain('Jumlah minimum');
        expect(modalTiers).toContain('value="100"');
        expect(modalTiers).toContain('value="15.00"');
        expect(modalTiers).not.toContain('>Tambah tier</button>');
        expect(modalTiers).not.toContain('>Hapus tier</button>');
    });
});
