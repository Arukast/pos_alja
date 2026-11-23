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
});
