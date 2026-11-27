import { ChevronLeft, ChevronRight } from 'lucide-react';
import { NavLink } from 'react-router';

import { ThemeToggle } from './ThemeToggle';
import { UserPopover } from './UserPopover';
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
                'border-light-border bg-light-surface relative hidden shrink-0 flex-col border-r transition-all duration-200 lg:flex dark:border-surface-300 dark:bg-surface-800',
                collapsed ? 'w-16' : 'w-60'
            )}
        >
            <div className="flex h-16 shrink-0 items-center gap-3 px-4">
                <img
                    src="/images/logo-alja-mark.png"
                    alt={collapsed ? 'Alja POS' : ''}
                    className="h-8 w-8 shrink-0 object-contain"
                />
                {!collapsed ? (
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold">Alja POS</p>
                        {user.branch?.name ? (
                            <p className="text-light-muted truncate text-xs dark:text-gray-400">
                                {user.branch.name}
                            </p>
                        ) : null}
                    </div>
                ) : null}
            </div>

            <nav className="min-h-0 flex-1 space-y-5 overflow-y-auto px-2 py-3">
                {groups.map((group) => (
                    <div key={group.label}>
                        {!collapsed ? (
                            <p className="text-light-muted px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest dark:text-gray-400">
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
                                                    'relative flex h-10 items-center gap-3 rounded-xl px-2.5 text-sm font-medium transition',
                                                    isActive
                                                        ? "bg-brand-fill text-white shadow-glow-primary before:absolute before:top-1/2 before:left-0 before:h-5 before:w-[3px] before:-translate-y-1/2 before:rounded-full before:bg-white before:content-['']"
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

            <div className="border-light-border flex shrink-0 flex-col gap-1 border-t p-2 dark:border-surface-300">
                <UserPopover compact={collapsed} />
                <ThemeToggle compact={collapsed} />
            </div>

            <button
                type="button"
                onClick={onToggleCollapsed}
                aria-label={collapsed ? 'Perluas bilah sisi' : 'Sempitkan bilah sisi'}
                className="border-light-border text-light-muted hover:text-brand-primary absolute top-20 -right-3 hidden h-6 w-6 bg-light-surface items-center justify-center rounded-full border shadow-sm transition lg:flex dark:border-surface-300 dark:bg-surface-700 dark:text-gray-400 dark:hover:text-brand-primary-light"
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
