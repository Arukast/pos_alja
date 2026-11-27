export function canEditCustomer(role: string | undefined): boolean {
    return role === 'owner' || role === 'manager' || role === 'designer';
}
