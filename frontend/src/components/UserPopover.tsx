import { LogOut, UserRound } from 'lucide-react';
import { useState } from 'react';
import { Link, useNavigate } from 'react-router';

import { useAuth } from '../auth/useAuth';
import { roleLabel } from '../lib/labels';

export function UserPopover({ compact = false }: { compact?: boolean }) {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);

    if (!user) {
        return null;
    }

    const initials =
        user.name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('') || 'U';

    const handleLogout = async () => {
        await logout();
        navigate('/login', { replace: true });
    };

    return (
        <div className="relative">
            {open ? (
                <div className="border-light-border shadow-card absolute bottom-full left-0 mb-2 w-52 rounded-xl border bg-white p-1.5 dark:border-surface-300 dark:bg-surface-700">
                    <p className="text-light-muted truncate px-2 py-1 text-xs dark:text-gray-400">
                        Masuk sebagai {user.username}
                    </p>
                    <Link
                        to="/profile"
                        onClick={() => setOpen(false)}
                        className="text-light-text hover:bg-light-surface-2 dark:hover:bg-surface-600 flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm dark:text-gray-200"
                    >
                        <UserRound className="h-4 w-4" />
                        Profil & Pengaturan
                    </Link>
                    <button
                        type="button"
                        onClick={() => void handleLogout()}
                        className="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10"
                    >
                        <LogOut className="h-4 w-4" />
                        Keluar
                    </button>
                </div>
            ) : null}
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="hover:bg-light-surface-2 dark:hover:bg-surface-600 flex w-full items-center gap-2 rounded-lg px-1.5 py-1.5 text-left transition"
                aria-expanded={open}
                aria-label="Buka menu akun"
            >
                <span className="bg-brand-primary/15 text-brand-primary dark:text-brand-primary-light flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                    {initials}
                </span>
                {compact ? null : (
                    <span className="min-w-0">
                        <span className="block truncate text-sm font-semibold">{user.name}</span>
                        <span className="text-light-muted block truncate text-xs dark:text-gray-400">
                            {roleLabel(user.role)}
                        </span>
                    </span>
                )}
            </button>
        </div>
    );
}
