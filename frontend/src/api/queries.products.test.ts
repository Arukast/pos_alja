import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../lib/api', () => ({
    API_BASE: '/api/v1',
    apiFetch: vi.fn(),
}));

import { api } from './queries';
import { apiFetch } from '../lib/api';

describe('api.products and admin product mutations', () => {
    const mockedApiFetch = vi.mocked(apiFetch);

    beforeEach(() => {
        mockedApiFetch.mockReset();
        mockedApiFetch.mockResolvedValue({});
    });

    it('requests paginated products and omits empty filter values', async () => {
        await api.products({ page: 1, search: '', category_id: '', is_active: '' });

        expect(mockedApiFetch).toHaveBeenCalledWith('/products?page=1');
    });

    it('serializes page and active filters for incremental product pagination', async () => {
        await api.products({
            page: 2,
            search: 'banner',
            category_id: 'cat-1',
            is_active: '1',
        });

        expect(mockedApiFetch).toHaveBeenCalledWith(
            '/products?page=2&search=banner&category_id=cat-1&is_active=1'
        );
    });

    it('calls create, update, delete, and restore product endpoints with expected payloads', async () => {
        const createBody = {
            name: 'Banner Vinyl',
            product_category_id: 'cat-1',
            pricing_type: 'area',
            is_active: true,
            material_ids: ['mat-1'],
        };
        await api.admin.createProduct(createBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/products', {
            method: 'POST',
            body: JSON.stringify(createBody),
        });

        const updateBody = { ...createBody, is_active: false };
        await api.admin.updateProduct('prod-1', updateBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/products/prod-1', {
            method: 'PUT',
            body: JSON.stringify(updateBody),
        });

        await api.admin.deleteProduct('prod-1');
        expect(mockedApiFetch).toHaveBeenCalledWith('/products/prod-1', {
            method: 'DELETE',
            body: undefined,
        });

        await api.admin.restoreProduct('prod-1');
        expect(mockedApiFetch).toHaveBeenCalledWith('/products/prod-1/restore', {
            method: 'POST',
            body: undefined,
        });
    });

    it('calls create and update category endpoints with description and active status', async () => {
        const createCategoryBody = {
            name: 'Print Lembaran',
            description: 'Cetak lembaran',
            is_active: true,
        };
        await api.admin.createCategory(createCategoryBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/categories', {
            method: 'POST',
            body: JSON.stringify(createCategoryBody),
        });

        const updateCategoryBody = {
            name: 'Print Lembaran Updated',
            description: null,
            is_active: false,
        };
        await api.admin.updateCategory('cat-1', updateCategoryBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/categories/cat-1', {
            method: 'PUT',
            body: JSON.stringify(updateCategoryBody),
        });
    });

    it('calls the material minimum-stock endpoint with a branch-scoped payload', async () => {
        const body = { branch_id: 'br-1', min_stock: 4.5 };

        await api.admin.setMaterialMinimumStock('mat-1', body);

        expect(mockedApiFetch).toHaveBeenCalledWith('/materials/mat-1/min-stock', {
            method: 'PATCH',
            body: JSON.stringify(body),
        });
    });

    it('calls material active and branch availability endpoints', async () => {
        await api.admin.setMaterialActive('mat-1', { is_active: false });
        expect(mockedApiFetch).toHaveBeenCalledWith('/materials/mat-1/active', {
            method: 'PATCH',
            body: JSON.stringify({ is_active: false }),
        });

        await api.admin.setMaterialBranchAvailability('mat-1', {
            branch_id: 'br-1',
            is_active: true,
        });
        expect(mockedApiFetch).toHaveBeenCalledWith('/materials/mat-1/branch-availability', {
            method: 'PATCH',
            body: JSON.stringify({ branch_id: 'br-1', is_active: true }),
        });
    });

    it('calls attribute type endpoints', async () => {
        await api.attributeTypes();
        expect(mockedApiFetch).toHaveBeenCalledWith('/attribute-types');

        await api.admin.createAttributeType({ name: 'Packaging', is_active: true });
        expect(mockedApiFetch).toHaveBeenCalledWith('/attribute-types', {
            method: 'POST',
            body: JSON.stringify({ name: 'Packaging', is_active: true }),
        });

        await api.admin.updateAttributeType('packaging', {
            name: 'Packaging Baru',
            is_active: false,
        });
        expect(mockedApiFetch).toHaveBeenCalledWith('/attribute-types/packaging', {
            method: 'PUT',
            body: JSON.stringify({ name: 'Packaging Baru', is_active: false }),
        });
    });

    it('serializes the include_inactive_attributes flag for quote options', async () => {
        await api.quoteOptions({ include_inactive_attributes: 1 });
        expect(mockedApiFetch).toHaveBeenCalledWith(
            '/quotes/options?include_inactive_attributes=1'
        );

        await api.quoteOptions();
        expect(mockedApiFetch).toHaveBeenCalledWith('/quotes/options');
    });

    it('calls attribute and branch admin endpoints with pricing, active status, and reseller discount', async () => {
        const attributeBody = {
            name: 'Laminasi Glossy',
            type: 'finishing',
            branch_id: null,
            price: 5000,
            cost: 2000,
            is_active: true,
        };
        await api.admin.createAttribute(attributeBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/attributes', {
            method: 'POST',
            body: JSON.stringify(attributeBody),
        });

        const attributeUpdate = { ...attributeBody, is_active: false };
        await api.admin.updateAttribute('attr-1', attributeUpdate);
        expect(mockedApiFetch).toHaveBeenCalledWith('/attributes/attr-1', {
            method: 'PUT',
            body: JSON.stringify(attributeUpdate),
        });

        const branchBody = {
            name: 'Cabang Pusat',
            code: 'PST',
            is_active: true,
            reseller_discount_percent: 12.5,
        };
        await api.admin.createBranch(branchBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/branches', {
            method: 'POST',
            body: JSON.stringify(branchBody),
        });

        const branchUpdate = { ...branchBody, reseller_discount_percent: 15 };
        await api.admin.updateBranch('br-1', branchUpdate);
        expect(mockedApiFetch).toHaveBeenCalledWith('/branches/br-1', {
            method: 'PUT',
            body: JSON.stringify(branchUpdate),
        });
    });
});
