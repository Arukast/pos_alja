import { describe, expect, it } from 'vitest';

import { roleHome } from './roleHome';

describe('roleHome', () => {
    it('maps every role to its dashboard', () => {
        expect(roleHome('owner')).toBe('/owner');
        expect(roleHome('manager')).toBe('/manager');
        expect(roleHome('cashier')).toBe('/cashier');
        expect(roleHome('designer')).toBe('/designer');
        expect(roleHome('production')).toBe('/production');
    });

    it('falls back to login for unknown roles', () => {
        expect(roleHome(null)).toBe('/login');
        expect(roleHome('unknown')).toBe('/login');
    });
});
