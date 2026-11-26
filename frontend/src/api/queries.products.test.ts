import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../lib/api', () => ({
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

    it('calls create, update, and delete category endpoints with discount_tiers payloads', async () => {
        const createCategoryBody = {
            name: 'Print Lembaran',
            discount_tiers: [
                { min_qty: 10, discount_percent: 5 },
                { min_qty: 50, discount_percent: 10 },
            ],
        };
        await api.admin.createCategory(createCategoryBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/categories', {
            method: 'POST',
            body: JSON.stringify(createCategoryBody),
        });

        const updateCategoryBody = {
            ...createCategoryBody,
            discount_tiers: [{ min_qty: 20, discount_percent: 7.5 }],
        };
        await api.admin.updateCategory('cat-1', updateCategoryBody);
        expect(mockedApiFetch).toHaveBeenCalledWith('/categories/cat-1', {
            method: 'PUT',
            body: JSON.stringify(updateCategoryBody),
        });

        await api.admin.deleteCategory('cat-1');
        expect(mockedApiFetch).toHaveBeenCalledWith('/categories/cat-1', {
            method: 'DELETE',
            body: undefined,
        });
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

        await api.admin.deleteAttribute('attr-1');
        expect(mockedApiFetch).toHaveBeenCalledWith('/attributes/attr-1', {
            method: 'DELETE',
            body: undefined,
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
