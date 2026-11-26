import { describe, expect, it } from 'vitest';

import { needsWasteReminder } from './wasteReminder';

describe('needsWasteReminder', () => {
    it('reminds only for a failed order without waste', () => {
        expect(needsWasteReminder({ status: 3, has_waste: false })).toBe(true);
        expect(needsWasteReminder({ status: 3, has_waste: true })).toBe(false);
        expect(needsWasteReminder({ status: 1, has_waste: false })).toBe(false);
    });
});
