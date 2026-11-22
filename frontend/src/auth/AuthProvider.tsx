import { useQueryClient } from '@tanstack/react-query';
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';

import { apiFetch, setUnauthorizedHandler } from '../lib/api';
import { AuthContext } from './context';
import type { AuthUser } from './types';

export function AuthProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const [user, setUser] = useState<AuthUser | null>(null);
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(async () => {
        try {
            const response = await apiFetch<{ user: AuthUser }>('/auth/me');
            setUser(response.user);
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        // oxlint-disable-next-line react/set-state-in-effect
        void refresh();
    }, [refresh]);

    useEffect(() => {
        setUnauthorizedHandler(() => {
            queryClient.clear();
            setUser(null);
        });

        return () => setUnauthorizedHandler(null);
    }, [queryClient]);

    const login = useCallback(
        async (username: string, password: string, remember = false) => {
            const response = await apiFetch<{ user: AuthUser }>('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ username, password, remember }),
            });

            setUser(response.user);

            return response.user;
        },
        []
    );

    const logout = useCallback(async () => {
        await apiFetch<void>('/auth/logout', { method: 'POST' });
        queryClient.clear();
        setUser(null);
    }, [queryClient]);

    const value = useMemo(
        () => ({ user, loading, login, logout, refresh }),
        [user, loading, login, logout, refresh]
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
