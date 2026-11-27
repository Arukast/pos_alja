import { describe, expect, it } from 'vitest';

import { defaultInstitution, institutionOptions } from './institutions';

describe('institutionOptions', () => {
    it('test_options_follow_customer_labels', () => {
        expect(
            institutionOptions({
                institutions: ['Sekolah', 'Universitas'],
                institution: 'Sekolah',
            }),
        ).toEqual(['Sekolah', 'Universitas']);
    });

    it('test_options_fall_back_to_legacy_singular_institution', () => {
        expect(institutionOptions({ institutions: [], institution: 'Sekolah' })).toEqual([
            'Sekolah',
        ]);
    });
});

describe('defaultInstitution', () => {
    it('test_default_is_first_label', () => {
        expect(
            defaultInstitution({
                institutions: ['Masyarakat/Retail', 'Organisasi/Yayasan'],
                institution: 'Organisasi/Yayasan',
            }),
        ).toBe('Masyarakat/Retail');
    });
});
