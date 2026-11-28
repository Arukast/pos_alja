import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../lib/api', () => ({
    API_BASE: '/api/v1',
    apiFetch: vi.fn(),
}));

import { api } from './queries';
import { apiFetch } from '../lib/api';

describe('api fail and reopen production orders', () => {
    const mockedApiFetch = vi.mocked(apiFetch);

    beforeEach(() => {
        mockedApiFetch.mockReset();
        mockedApiFetch.mockResolvedValue({});
    });

    it('posts the fail note for an order', async () => {
        await api.failOrder('order-1', { note: 'Bahan rusak' });

        expect(mockedApiFetch).toHaveBeenCalledWith('/orders/order-1/fail', {
            method: 'POST',
            body: JSON.stringify({ note: 'Bahan rusak' }),
        });
    });

    it('posts reopen without a body', async () => {
        await api.reopenOrder('order-1');

        expect(mockedApiFetch).toHaveBeenCalledWith('/orders/order-1/reopen', {
            method: 'POST',
            body: undefined,
        });
    });
});
