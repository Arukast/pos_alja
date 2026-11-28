import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

import type { Attribute, AttributeType, Material, Product } from '../../api/types';

const { responses } = vi.hoisted(() => ({ responses: new Map<string, unknown>() }));

vi.mock('../../auth/useAuth', () => ({
    useAuth: () => ({ user: { role: 'manager' } }),
}));

vi.mock('@tanstack/react-query', async () => {
    const actual = await vi.importActual<typeof import('@tanstack/react-query')>(
        '@tanstack/react-query'
    );

    return {
        ...actual,
        useQueryClient: () => ({ invalidateQueries: vi.fn() }),
        useQuery: ({ queryKey, enabled }: { queryKey: unknown[]; enabled?: boolean }) => ({
            data: enabled === false ? undefined : responses.get(JSON.stringify(queryKey)),
            isPending: false,
            isError: false,
        }),
    };
});

vi.mock('../../components/admin/CrudPage', () => ({
    CrudPage: ({ fields }: { fields: Array<{
        key: string;
        label: string;
        type?: string;
        options?: Array<{ value: string; label: string }>;
    }> }) => (
        <div>
            {fields
                .filter((field) => field.type === 'multiselect')
                .map((field) => (
                    <div key={field.key}>
                        <span>{field.label}</span>
                        {(field.options ?? []).length === 0 ? (
                            <span>Belum ada pilihan.</span>
                        ) : (
                            (field.options ?? []).map((option) => (
                                <span key={option.value}>{option.label}</span>
                            ))
                        )}
                    </div>
                ))}
        </div>
    ),
}));

import { ManagerProductsPage } from '../manager/AdminPages';

describe('read-only product modal compatibility options', () => {
    it('renders stored material and attribute option labels instead of the empty message', () => {
        const material: Material = {
            id: 'm1',
            name: 'Kain Katun',
            type: 'roll',
            dimensions: null,
            unit: 'meter',
            cost: '1000',
            price: '2000',
            is_active: true,
            category: null,
            current_stock: null,
            min_stock: null,
            is_low_stock: null,
            stocks: [],
        };
        const attributeType: AttributeType = {
            id: 'at1',
            name: 'Finishing',
            slug: 'finishing',
            sort_order: 1,
            is_active: true,
        };
        const attribute: Attribute = {
            id: 'a1',
            name: 'Glossy',
            type: 'finishing',
            type_name: 'Finishing',
            price: '500',
            cost: '250',
            is_active: true,
            branch_id: null,
            branch: null,
        };
        const product: Product = {
            id: 'p1',
            name: 'Spanduk',
            selling_unit: 'pcs',
            is_active: true,
            category: null,
            materials: [material],
            material_ids: ['m1'],
            requires_material: true,
            uses_attributes: true,
            attribute_types: [attributeType],
            attribute_type_ids: ['at1'],
            compatible_attributes: [attribute],
            attribute_ids: ['a1'],
            tiers: [],
        };

        responses.set(JSON.stringify(['materials', 'all-options']), [material]);
        responses.set(JSON.stringify(['attribute-types', 'product-options']), {
            data: [attributeType],
        });
        responses.set(JSON.stringify(['attributes', 'product-options']), { data: [attribute] });
        responses.set(JSON.stringify(['manager', 'products', '', '', '', 1]), {
            data: [product],
        });

        const html = renderToStaticMarkup(<ManagerProductsPage />);

        expect(html).toContain('Kain Katun');
        expect(html).toContain('Finishing');
        expect(html).toContain('Glossy · Finishing');
        expect(html).not.toContain('Belum ada pilihan.');
    });
});
