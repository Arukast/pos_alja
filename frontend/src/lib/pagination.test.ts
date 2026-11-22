import { describe, expect, it } from 'vitest';

import { pageWindow } from './pagination';

describe('pageWindow', () => {
    it('returns the visible range on a middle page', () => {
        expect(pageWindow(2, 10, 23)).toEqual({ from: 11, to: 20 });
    });

    it('clamps the last page', () => {
        expect(pageWindow(3, 10, 23)).toEqual({ from: 21, to: 23 });
    });

    it('returns zeros for an empty result', () => {
        expect(pageWindow(1, 10, 0)).toEqual({ from: 0, to: 0 });
    });

    it('returns zeros when the page is beyond the total', () => {
        expect(pageWindow(4, 10, 23)).toEqual({ from: 0, to: 0 });
    });

    it('handles a single-row result', () => {
        expect(pageWindow(1, 10, 1)).toEqual({ from: 1, to: 1 });
    });
});
