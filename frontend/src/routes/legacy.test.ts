import { describe, expect, it } from 'vitest';

import { legacyTarget } from './legacyTargets';

describe('legacyTarget', () => {
    it('redirects the old manager pricing tab to quantity tiers', () => {
        expect(legacyTarget('manager', 'pricing')).toBe('/manager/quantity-tiers');
    });

    it('keeps the older quantity_tiers alias working', () => {
        expect(legacyTarget('manager', 'quantity_tiers')).toBe('/manager/quantity-tiers');
    });

    it('falls back to the role home for unknown tabs', () => {
        expect(legacyTarget('manager', 'unknown')).toBe('/manager');
    });
});
