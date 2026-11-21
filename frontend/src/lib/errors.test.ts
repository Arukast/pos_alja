import { describe, expect, it } from 'vitest';

import { ApiError, errorMessage, mapApiErrors } from './errors';

describe('mapApiErrors', () => {
    it('keeps the first message per field', () => {
        expect(
            mapApiErrors({
                message: 'Validation failed',
                errors: {
                    amount: ['too high', 'also required'],
                    name: ['required'],
                },
            })
        ).toEqual({
            amount: 'too high',
            name: 'required',
        });
    });

    it('returns an empty map for unknown payloads', () => {
        expect(mapApiErrors(null)).toEqual({});
        expect(mapApiErrors('nope')).toEqual({});
    });
});

describe('errorMessage', () => {
    it('prefers the first field error', () => {
        const error = new ApiError(422, {
            message: 'Validation failed',
            errors: { amount: ['too high'] },
        });

        expect(errorMessage(error)).toBe('too high');
    });

    it('falls back to a plain error message', () => {
        expect(errorMessage(new Error('boom'))).toBe('boom');
    });

    it('has a generic fallback', () => {
        expect(errorMessage(undefined)).toBe('Something went wrong.');
    });
});
