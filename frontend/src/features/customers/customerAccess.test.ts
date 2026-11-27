import { describe, expect, it } from 'vitest';

import { canEditCustomer } from './customerAccess';

describe('canEditCustomer', () => {
    it('test_only_owner_manager_designer_can_edit', () => {
        expect(canEditCustomer('owner')).toBe(true);
        expect(canEditCustomer('manager')).toBe(true);
        expect(canEditCustomer('designer')).toBe(true);

        expect(canEditCustomer('cashier')).toBe(false);
        expect(canEditCustomer('production')).toBe(false);
        expect(canEditCustomer(undefined)).toBe(false);
    });
});
