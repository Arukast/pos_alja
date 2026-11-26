import { describe, expect, it } from 'vitest';

import { auditPayloadView } from './audit';

describe('auditPayloadView', () => {
    it('renders a create payload (after only) as a comparison with a dash before', () => {
        expect(auditPayloadView({ after: { name: 'Kertas', nested: { gramasi: 80 } } })).toEqual({
            kind: 'comparison',
            rows: [
                { key: 'name', before: '-', after: 'Kertas' },
                { key: 'nested', before: '-', after: '{"gramasi":80}' },
            ],
        });
    });

    it('renders a delete payload (after null) as a comparison with a dash after', () => {
        expect(auditPayloadView({ before: { name: 'Kertas', tags: ['a'] }, after: null })).toEqual({
            kind: 'comparison',
            rows: [
                { key: 'name', before: 'Kertas', after: '-' },
                { key: 'tags', before: '["a"]', after: '-' },
            ],
        });
    });

    it('unions before and after keys and stringifies scalars and nested values', () => {
        expect(
            auditPayloadView({
                before: { name: 'Lama', quantity: 2, deleted: false },
                after: { name: 'Baru', nested: { unit: 'rim' } },
            })
        ).toEqual({
            kind: 'comparison',
            rows: [
                { key: 'name', before: 'Lama', after: 'Baru' },
                { key: 'quantity', before: '2', after: '-' },
                { key: 'deleted', before: 'false', after: '-' },
                { key: 'nested', before: '-', after: '{"unit":"rim"}' },
            ],
        });
    });

    it('renders a flat payload as key/value rows', () => {
        expect(
            auditPayloadView({ quantity: 5, stock_before: 1.5, notes: null, extra: { a: 1 } })
        ).toEqual({
            kind: 'fields',
            rows: [
                { key: 'quantity', value: '5' },
                { key: 'stock_before', value: '1.5' },
                { key: 'notes', value: '-' },
                { key: 'extra', value: '{"a":1}' },
            ],
        });
    });

    it('falls back to key/value rows when before/after are mixed with other keys', () => {
        expect(auditPayloadView({ before: { a: 1 }, reason: 'koreksi' })).toEqual({
            kind: 'fields',
            rows: [
                { key: 'before', value: '{"a":1}' },
                { key: 'reason', value: 'koreksi' },
            ],
        });
    });

    it('returns empty for null and empty payloads', () => {
        expect(auditPayloadView(null)).toEqual({ kind: 'empty' });
        expect(auditPayloadView({})).toEqual({ kind: 'empty' });
    });
});
