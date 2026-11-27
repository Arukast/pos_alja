import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../lib/api', () => ({
    API_BASE: '/api/v1',
    apiFetch: vi.fn(),
}));

import { api } from './queries';
import { apiFetch } from '../lib/api';

describe('api.admin.toggleUser', () => {
    const mockedApiFetch = vi.mocked(apiFetch);

    beforeEach(() => {
        mockedApiFetch.mockReset();
        mockedApiFetch.mockResolvedValue({});
    });

    it('toggles a user status with PATCH', async () => {
        await api.admin.toggleUser('user-1');

        expect(mockedApiFetch).toHaveBeenCalledWith('/users/user-1/status', {
            method: 'PATCH',
            body: undefined,
        });
    });
});
