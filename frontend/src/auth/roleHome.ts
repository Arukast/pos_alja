export type Role = 'owner' | 'manager' | 'cashier' | 'designer' | 'production';

const homes: Record<Role, string> = {
    owner: '/owner',
    manager: '/manager',
    cashier: '/cashier',
    designer: '/designer',
    production: '/production',
};

export function roleHome(role: string | null | undefined): string {
    return role && role in homes ? homes[role as Role] : '/login';
}
