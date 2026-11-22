import { X } from 'lucide-react';
import { NavLink } from 'react-router';

import { useAuth } from '../auth/useAuth';
import { cn } from '../lib/cn';
import { roleLinks } from './navigation/roleLinks';

export function MobileDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { user } = useAuth();

    if (!open || !user) {
        return null;
    }

    const groups = roleLinks[user.role] ?? [];

    return (
        <div className="fixed inset-0 z-40 lg:hidden">
            <button
                type="button"
                aria-label="Tutup navigasi"
                className="absolute inset-0 bg-black/50"
                onClick={onClose}
            />
            <aside className="border-light-border absolute inset-y-0 left-0 flex w-60 flex-col border-r bg-white shadow-xl dark:border-surface-300 dark:bg-surface-800">
                <div className="flex h-16 items-center justify-between px-4">
                    <div className="flex items-center gap-3">
                        <span className="from-brand-primary to-brand-secondary h-8 w-8 rounded-xl bg-gradient-to-br" />
                        <p className="text-sm font-bold">Alja POS</p>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup navigasi"
                        className="text-light-muted rounded-lg p-1.5 hover:bg-light-surface-2 dark:text-gray-400 dark:hover:bg-surface-600"
                        onClick={onClose}
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
                <nav className="flex-1 space-y-5 overflow-y-auto px-2 py-3">
                    {groups.map((group) => (
                        <div key={group.label}>
                            <p className="text-light-muted px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest dark:text-gray-500">
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
                                                    cn(
                                                        'flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-medium transition',
                                                        isActive
                                                            ? 'bg-brand-primary/10 text-brand-primary dark:bg-brand-primary/15 dark:text-brand-primary-light'
                                                            : 'text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:text-gray-400 dark:hover:bg-surface-600 dark:hover:text-gray-100'
                                                    )
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
            </aside>
        </div>
    );
}
