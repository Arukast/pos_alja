import { X } from 'lucide-react';
import { useEffect } from 'react';
import { NavLink } from 'react-router';

import { ThemeToggle } from './ThemeToggle';
import { UserPopover } from './UserPopover';
import { useAuth } from '../auth/useAuth';
import { roleLinks } from './navigation/roleLinks';

export function MobileDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { user } = useAuth();

    useEffect(() => {
        if (!open) {
            return;
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open, onClose]);

    if (!open || !user) {
        return null;
    }

    const groups = roleLinks[user.role] ?? [];

    return (
        <div className="fixed inset-0 z-40 lg:hidden">
            <button
                type="button"
                aria-label="Tutup navigasi"
                className="bg-brand-dark/50 dark:bg-black/70 absolute inset-0"
                onClick={onClose}
            />
            <aside className="border-light-border bg-light-surface absolute inset-y-0 left-0 flex w-[280px] max-w-[85vw] flex-col border-r shadow-xl dark:border-surface-300 dark:bg-surface-800">
                <div className="flex h-16 shrink-0 items-center justify-between px-4">
                    <div className="flex min-w-0 items-center gap-2.5">
                        <img src="/images/logo-alja-mark-96.png" alt="" className="h-8 w-8 shrink-0" />
                        <p className="truncate text-sm font-bold">Alja POS</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup navigasi"
                        className="text-light-muted hover:bg-light-surface-2 hover:text-light-text rounded-xl p-1.5 dark:text-gray-400 dark:hover:bg-surface-600 dark:hover:text-gray-100"
                        onClick={onClose}
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <nav className="min-h-0 flex-1 space-y-5 overflow-y-auto px-2 py-3">
                    {groups.map((group) => (
                        <div key={group.label}>
                            <p className="text-light-muted px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest dark:text-gray-400">
                                {group.label}
                            </p>
                            <ul className="space-y-1">
                                {group.links.map((link) => {
                                    const Icon = link.icon;

                                    return (
                                        <li key={link.to}>
                                            <NavLink
                                                to={link.to}
                                                end={link.end}
                                                onClick={onClose}
                                                className={({ isActive }) =>
                                                    isActive
                                                        ? "bg-brand-fill text-white shadow-glow-primary relative flex h-10 items-center gap-3 rounded-xl px-2.5 text-sm font-medium transition before:absolute before:top-1/2 before:left-0 before:h-5 before:w-[3px] before:-translate-y-1/2 before:rounded-full before:bg-white before:content-['']"
                                                        : 'text-light-muted hover:bg-light-surface-2 hover:text-light-text flex h-10 items-center gap-3 rounded-xl px-2.5 text-sm font-medium transition dark:text-gray-400 dark:hover:bg-surface-600 dark:hover:text-gray-100'
                                                }
                                            >
                                                <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />
                                                <span>{link.label}</span>
                                            </NavLink>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>

                <div className="border-light-border flex shrink-0 flex-col gap-1 border-t p-2 dark:border-surface-300">
                    <UserPopover onNavigate={onClose} />
                    <ThemeToggle />
                </div>
            </aside>
        </div>
    );
}
