import { createContext } from 'react';

import type { AuthUser } from './types';

export interface AuthContextValue {
    user: AuthUser | null;
    loading: boolean;
    login: (username: string, password: string, remember?: boolean) => Promise<AuthUser>;
    logout: () => Promise<void>;
    refresh: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
