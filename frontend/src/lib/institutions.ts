import type { Customer } from '../api/types';

export function institutionOptions(
    customer: Pick<Customer, 'institutions' | 'institution'>,
): string[] {
    return customer.institutions?.length ? customer.institutions : [customer.institution];
}

export function defaultInstitution(
    customer: Pick<Customer, 'institutions' | 'institution'>,
): string {
    return institutionOptions(customer)[0];
}
