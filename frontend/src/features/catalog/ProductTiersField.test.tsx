import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

import { ProductTiersField, productPayload, type ProductTierValue } from './ProductTiersField';

const rows: ProductTierValue[] = [
    { id: 't1', min_qty: 50, discount_percent: '10.00' },
    { id: 't2', min_qty: 100, discount_percent: '20.00' },
];

describe('ProductTiersField', () => {
    it('test_tier_editor_renders_stored_rows_with_indonesian_labels', () => {
        const html = renderToStaticMarkup(
            <ProductTiersField value={rows} onChange={vi.fn()} errors={{ 'tiers.1.discount_percent': 'Diskon tidak valid' }} />
        );

        expect(html).toContain('Tier Jumlah');
        expect(html).toContain('Jumlah minimum');
        expect(html).toContain('Diskon');
        expect(html).toContain('value="50"');
        expect(html).toContain('value="10.00"');
        expect(html).toContain('Diskon tidak valid');
        expect(html).toContain('>Tambah tier</button>');
        expect(html).toContain('>Hapus tier</button>');
    });

    it('test_tier_editor_has_no_default_row_and_hides_actions_when_disabled', () => {
        const empty = renderToStaticMarkup(<ProductTiersField value={[]} onChange={vi.fn()} errors={{}} />);
        expect(empty).not.toContain('value="1"');
        expect(empty).not.toContain('>Hapus tier</button>');

        const disabled = renderToStaticMarkup(
            <ProductTiersField value={rows} onChange={vi.fn()} errors={{}} disabled />
        );
        expect(disabled).not.toContain('>Tambah tier</button>');
        expect(disabled).not.toContain('>Hapus tier</button>');
        expect(disabled).toContain('disabled');
    });

    it('test_product_payload_preserves_hidden_pivot_ids_and_all_tiers', () => {
        const payload = productPayload({
            name: 'Spanduk',
            product_category_id: 'cat-1',
            selling_unit: 'pcs',
            requires_material: false,
            uses_attributes: false,
            material_ids: ['mat-1', 'mat-2'],
            attribute_type_ids: ['type-1'],
            attribute_ids: ['attr-1'],
            price: '0.00',
            cost: '0.00',
            tiers: rows,
        });

        expect(payload).toMatchObject({
            price: '0.00',
            cost: '0.00',
            material_ids: ['mat-1', 'mat-2'],
            attribute_type_ids: ['type-1'],
            attribute_ids: ['attr-1'],
            tiers: [
                { id: 't1', min_qty: 50, discount_percent: '10.00' },
                { id: 't2', min_qty: 100, discount_percent: '20.00' },
            ],
        });
    });
});
