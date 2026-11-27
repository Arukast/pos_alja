import { describe, expect, it } from 'vitest';

import { CUSTOMER_INSTITUTIONS } from './types';

describe('CUSTOMER_INSTITUTIONS', () => {
    it('test_customer_institution_constants_match_backend', () => {
        expect(CUSTOMER_INSTITUTIONS).toEqual([
            'Masyarakat/Retail',
            'Sekolah',
            'Universitas',
            'Pemerintahan',
            'Bisnis/Perusahaan',
            'Organisasi/Yayasan',
        ]);
        expect(CUSTOMER_INSTITUTIONS).toContain('Bisnis/Perusahaan');
        expect(CUSTOMER_INSTITUTIONS).toContain('Organisasi/Yayasan');
        expect(CUSTOMER_INSTITUTIONS[0]).toBe('Masyarakat/Retail');
    });
});
