import { describe, expect, it } from 'vitest';

import { legacyTarget } from './legacyTargets';

describe('legacyTarget', () => {
    it('redirects the old manager pricing tab to products', () => {
        expect(legacyTarget('manager', 'pricing')).toBe('/manager/products');
    });

    it('redirects the manager quantity_tiers alias to products', () => {
        expect(legacyTarget('manager', 'quantity_tiers')).toBe('/manager/products');
    });

    it('redirects the owner quantity_tiers alias to products', () => {
        expect(legacyTarget('owner', 'quantity_tiers')).toBe('/owner/products');
    });

    it('falls back to the role home for unknown tabs', () => {
        expect(legacyTarget('manager', 'unknown')).toBe('/manager');
    });
});
