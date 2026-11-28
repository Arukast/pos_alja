import { afterEach, describe, expect, it, vi } from 'vitest';

import { apiDownload, extractDownloadFilename, setUnauthorizedHandler } from './api';
import { ApiError } from './errors';

describe('extractDownloadFilename', () => {
    it('extracts quoted, unquoted, and UTF-8 encoded filenames', () => {
        expect(
            extractDownloadFilename(
                'attachment; filename="laporan-gross-revenue-2026-11-27.xlsx"',
                'fallback.xlsx'
            )
        ).toBe('laporan-gross-revenue-2026-11-27.xlsx');

        expect(
            extractDownloadFilename('attachment; filename=laporan.pdf', 'fallback.pdf')
        ).toBe('laporan.pdf');

        expect(
            extractDownloadFilename(
                "attachment; filename*=UTF-8''laporan%20laba%20bersih.pdf",
                'fallback.pdf'
            )
        ).toBe('laporan laba bersih.pdf');

        expect(extractDownloadFilename(null, 'fallback.xlsx')).toBe('fallback.xlsx');
    });
});

describe('apiDownload', () => {
    afterEach(() => {
        setUnauthorizedHandler(null);
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('downloads blob with parsed filename and revokes object URL', async () => {
        const click = vi.fn();
        const remove = vi.fn();
        const anchor = {
            href: '',
            download: '',
            click,
            remove,
        };
        const appendChild = vi.fn();
        const createObjectURL = vi.fn(() => 'blob:mock-report');
        const revokeObjectURL = vi.fn();

        vi.stubGlobal('document', {
            cookie: 'XSRF-TOKEN=abc%20123',
            createElement: vi.fn(() => anchor),
            body: { appendChild },
        });
        vi.stubGlobal('URL', { createObjectURL, revokeObjectURL });
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                new Response(new Blob(['xlsx-bytes']), {
                    status: 200,
                    headers: {
                        'Content-Disposition':
                            'attachment; filename="laporan-net-profit-2026-11-27.xlsx"',
                    },
                })
            )
        );

        const filename = await apiDownload(
            '/reports/net-profit/export?format=xlsx',
            'fallback.xlsx'
        );

        expect(filename).toBe('laporan-net-profit-2026-11-27.xlsx');
        expect(anchor.download).toBe('laporan-net-profit-2026-11-27.xlsx');
        expect(click).toHaveBeenCalledTimes(1);
        expect(remove).toHaveBeenCalledTimes(1);
        expect(revokeObjectURL).toHaveBeenCalledWith('blob:mock-report');
    });

    it('invokes unauthorized handler on 401 and throws ApiError on JSON failure', async () => {
        const onUnauthorized = vi.fn();
        setUnauthorizedHandler(onUnauthorized);

        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                new Response(JSON.stringify({ message: 'Sesi berakhir.' }), {
                    status: 401,
                    headers: { 'Content-Type': 'application/json' },
                })
            )
        );

        await expect(apiDownload('/reports/expenses/export?format=pdf', 'fallback.pdf')).rejects.toThrow(
            ApiError
        );
        expect(onUnauthorized).toHaveBeenCalledTimes(1);

        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                new Response(JSON.stringify({ message: 'Akses ditolak.' }), {
                    status: 403,
                    headers: { 'Content-Type': 'application/json' },
                })
            )
        );

        await expect(
            apiDownload('/reports/expenses/export?format=pdf', 'fallback.pdf')
        ).rejects.toMatchObject({ status: 403, message: 'Akses ditolak.' });
    });
});
