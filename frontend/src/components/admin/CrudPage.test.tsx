import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { type ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

vi.mock('../ui/Modal', () => ({
    Modal: ({ children, title }: { children: ReactNode; title: string }) => <div>{title}{children}</div>,
}));

import { CrudPage, valuesForFields, type CrudField } from './CrudPage';

interface Row { id: string; name: string; flag: boolean; tags: string[] }
const row: Row = { id: 'r1', name: 'Spanduk', flag: false, tags: ['m1'] };

function renderPage(fields: Array<CrudField<Row>>, extra: Record<string, unknown> = {}): string {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    client.setQueryData(['items', 1], {
        data: [row],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
    });

    return renderToStaticMarkup(
        <QueryClientProvider client={client}>
            <CrudPage
                eyebrow="Admin"
                title="Daftar item"
                queryKey={['items']}
                queryFn={() => Promise.resolve({ data: [row], links: { first: null, last: null, prev: null, next: null }, meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 } })}
                columns={[{ header: 'Nama', cell: (item: Row) => item.name }]}
                fields={fields}
                hideBanner
                {...extra}
            />
        </QueryClientProvider>
    );
}

describe('CrudPage', () => {
    it('test_hideWhen_removes_fields_from_the_modal', () => {
        const html = renderPage([
            { key: 'name', label: 'Nama' },
            { key: 'secret', label: 'Hidden field', hideWhen: () => true },
        ]);

        expect(html).toContain('Nama');
        expect(html).not.toContain('Hidden field');
    });

    it('test_valuesForFields_keeps_hidden_field_keys', () => {
        const fields: Array<CrudField<Row>> = [
            { key: 'name', label: 'Nama', getValue: (item) => item.name },
            { key: 'secret', label: 'Hidden field', hideWhen: () => true, getValue: () => 'kept' },
        ];

        const values = valuesForFields(row, fields);
        expect(values).toMatchObject({ name: 'Spanduk', secret: 'kept' });
        expect(Object.keys(values)).toEqual(['name', 'secret']);
    });

    it('test_read_only_mode_offers_Lihat_and_disables_all_fields', () => {
        const html = renderPage(
            [
                { key: 'name', label: 'Nama' },
                { key: 'flag', label: 'Aktif', type: 'checkbox' },
                { key: 'category', label: 'Kategori', type: 'select', options: [{ value: 'a', label: 'A' }] },
            ],
            {
                readOnly: true,
                viewTitle: 'Lihat data',
                create: () => Promise.resolve(),
                update: () => Promise.resolve(),
                remove: () => Promise.resolve(),
            }
        );

        expect(html).toContain('>Lihat</button>');
        expect(html).toContain('Lihat data');
        expect(html).not.toContain('>Simpan</button>');
        expect(html).not.toContain('>Tambah data</button>');
        expect(html).not.toContain('>Ubah</button>');
        expect(html).not.toContain('>Hapus</button>');

        const modalMarkup = html.slice(html.indexOf('Lihat data'));
        expect(modalMarkup.match(/<input[^>]*disabled/g)).toHaveLength(2);
        expect(modalMarkup).toMatch(/<select[^>]*disabled/);
    });
});
