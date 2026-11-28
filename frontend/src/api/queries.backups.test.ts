import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('../lib/api', () => ({
    API_BASE: '/api/v1',
    apiFetch: vi.fn(),
}));

import { api } from './queries';
import { apiFetch } from '../lib/api';

describe('api.backupDownloadUrl', () => {
    const mockedApiFetch = vi.mocked(apiFetch);

    beforeEach(() => {
        mockedApiFetch.mockReset();
    });

    it('builds the download URL from the shared API base', () => {
        expect(api.backupDownloadUrl('backup-1')).toBe('/api/v1/backups/backup-1/download');
    });
});
