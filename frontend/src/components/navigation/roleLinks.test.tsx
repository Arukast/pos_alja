import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';

import { roleLinks } from './roleLinks';

const linksFor = (role: string): string => renderToStaticMarkup(
    <nav>{roleLinks[role]?.flatMap((group) => group.links).map((link) => (
        <a key={link.to} href={link.to}>{link.label}</a>
    ))}</nav>
);

describe('roleLinks', () => {
    it('does not advertise standalone tier pages', () => {
        for (const role of ['owner', 'manager']) {
            const html = linksFor(role);

            expect(html).not.toContain('Tier Jumlah');
            expect(html).not.toContain('/owner/quantity-tiers');
            expect(html).not.toContain('/manager/quantity-tiers');
            expect(html).toContain(`/${role}/products`);
        }
    });
});
