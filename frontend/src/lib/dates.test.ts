import { describe, expect, it } from 'vitest';

import { formatDate, periodRange } from './dates';

describe('periodRange', () => {
    const reference = new Date(2026, 10, 20, 12, 0, 0);

    it('returns the reference day for daily', () => {
        expect(periodRange('daily', reference)).toEqual({
            start: '2026-11-20',
            end: '2026-11-20',
        });
    });

    it('returns monday to sunday for weekly', () => {
        expect(periodRange('weekly', reference)).toEqual({
            start: '2026-11-16',
            end: '2026-11-22',
        });
    });

    it('returns the calendar month for monthly', () => {
        expect(periodRange('monthly', reference)).toEqual({
            start: '2026-11-01',
            end: '2026-11-30',
        });
    });
});

describe('formatDate', () => {
    it('formats an ISO date without timezone drift', () => {
        expect(formatDate('2026-11-20')).toBe('20 Nov 2026');
        expect(formatDate('2026-05-20')).toBe('20 Mei 2026');
        expect(formatDate('2026-10-21')).toBe('21 Okt 2026');
    });
});
