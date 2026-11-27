import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ComponentType } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';

import { AuthProvider } from '../../auth/AuthProvider';
import { OwnerQuantityTiersPage } from '../owner/AdminPages';
import { ManagerQuantityTiersPage } from './AdminPages';

// No jsdom or Testing Library in this repo: server markup is enough to prove the
// read-only manager view renders the tier columns without the create button.
function renderPage(Page: ComponentType, prefix: string): string {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

    queryClient.setQueryData([prefix, 'quantity-tiers', 1], {
        data: [],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    });

    return renderToStaticMarkup(
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <Page />
            </AuthProvider>
        </QueryClientProvider>
    );
}

describe('quantity tiers pages', () => {
    it('shows tier columns to a manager without a create button', () => {
        const html = renderPage(ManagerQuantityTiersPage, 'manager');

        expect(html).toContain('Jml min');
        expect(html).toContain('Diskon');
        expect(html).not.toContain('>Tambah tier</button>');
    });

    it('shows the create button to the owner', () => {
        const html = renderPage(OwnerQuantityTiersPage, 'owner');

        expect(html).toContain('>Tambah tier</button>');
    });
});
