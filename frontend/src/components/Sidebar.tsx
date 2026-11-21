import { ChevronLeft, ChevronRight } from 'lucide-react';
import { NavLink } from 'react-router';

import { useAuth } from '../auth/useAuth';
import { cn } from '../lib/cn';
import { roleLinks } from './navigation/roleLinks';

export function Sidebar({
    collapsed,
    onToggleCollapsed,
}: {
    collapsed: boolean;
    onToggleCollapsed: () => void;
}) {
    const { user } = useAuth();

    if (!user) {
        return null;
    }

    const groups = roleLinks[user.role] ?? [];

    return (
        <aside
            className={cn(
                'border-light-border relative hidden shrink-0 border-r bg-white transition-all duration-200 lg:flex lg:flex-col dark:border-surface-300 dark:bg-surface-800',
                collapsed ? 'w-16' : 'w-60'
            )}
        >
            <div className="flex h-16 items-center gap-3 px-4">
                <span className="from-brand-primary to-brand-secondary h-8 w-8 shrink-0 rounded-xl bg-gradient-to-br" />
                {!collapsed ? (
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold">Alja POS</p>
                        <p className="text-light-muted truncate text-xs dark:text-gray-400">
                            {user.branch?.name ?? 'All branches'}
                        </p>
                    </div>
                ) : null}
            </div>

            <nav className="flex-1 space-y-5 overflow-y-auto px-2 py-3">
                {groups.map((group) => (
                    <div key={group.label}>
                        {!collapsed ? (
                            <p className="text-light-muted px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest dark:text-gray-500">
                                {group.label}
                            </p>
                        ) : null}
                        <ul className="space-y-1">
                            {group.links.map((link) => {
                                const Icon = link.icon;

                                return (
                                    <li key={link.to}>
                                        <NavLink
                                            to={link.to}
                                            end={link.end}
                                            title={collapsed ? link.label : undefined}
                                            className={({ isActive }) =>
                                                cn(
                                                    'flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-medium transition',
                                                    isActive
                                                        ? 'bg-brand-primary/10 text-brand-primary dark:bg-brand-primary/15 dark:text-brand-primary-light'
                                                        : 'text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:text-gray-400 dark:hover:bg-surface-600 dark:hover:text-gray-100',
                                                    collapsed && 'justify-center px-2'
                                                )
                                            }
                                        >
                                            <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />
                                            {!collapsed ? <span>{link.label}</span> : null}
                                        </NavLink>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>

            <button
                type="button"
                onClick={onToggleCollapsed}
                aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                className="border-light-border text-light-muted absolute top-20 -right-3 hidden h-6 w-6 items-center justify-center rounded-full border bg-white shadow-sm transition hover:text-brand-primary lg:flex dark:border-surface-300 dark:bg-surface-700 dark:text-gray-400"
            >
                {collapsed ? (
                    <ChevronRight className="h-3.5 w-3.5" />
                ) : (
                    <ChevronLeft className="h-3.5 w-3.5" />
                )}
            </button>
        </aside>
    );
}
