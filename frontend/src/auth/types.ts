export interface AuthBranch {
    id: string;
    name: string;
    code: string | null;
}

export interface AuthUser {
    id: string;
    name: string;
    username: string;
    email: string | null;
    role: string;
    phone: string | null;
    address: string | null;
    branch: AuthBranch | null;
}
