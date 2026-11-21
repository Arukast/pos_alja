import { Menu } from 'lucide-react';
import { useState } from 'react';
import { Outlet } from 'react-router';

import { MobileDrawer } from './MobileDrawer';
import { Sidebar } from './Sidebar';
import { ThemeToggle } from './ThemeToggle';
import { UserPopover } from './UserPopover';
import { useOrderUpdates } from '../hooks/useOrderUpdates';

export function AppShell() {
    useOrderUpdates();

    const [collapsed, setCollapsed] = useState(
        () => localStorage.getItem('sidebarCollapsed') === 'true'
    );
    const [mobileOpen, setMobileOpen] = useState(false);

    const toggleCollapsed = () => {
        setCollapsed((value) => {
            localStorage.setItem('sidebarCollapsed', String(!value));

            return !value;
        });
    };

    return (
        <div className="bg-light-bg text-light-text dark:bg-surface-900 flex h-screen overflow-hidden dark:text-gray-100">
            <Sidebar collapsed={collapsed} onToggleCollapsed={toggleCollapsed} />

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="border-light-border flex h-14 items-center justify-between border-b px-4 lg:hidden dark:border-surface-300">
                    <button
                        type="button"
                        aria-label="Buka navigasi"
                        className="text-light-muted hover:bg-light-surface-2 dark:hover:bg-surface-600 rounded-lg p-2 dark:text-gray-400"
                        onClick={() => setMobileOpen(true)}
                    >
                        <Menu className="h-5 w-5" />
                    </button>
                    <span className="text-sm font-bold">Alja POS</span>
                    <ThemeToggle compact />
                </header>

                <main className="flex-1 overflow-y-auto">
                    <div className="mx-auto w-full max-w-[1600px] p-4 sm:p-6">
                        <Outlet />
                    </div>
                </main>

                <div className="border-light-border hidden items-center justify-between gap-2 border-t px-3 py-2 lg:flex dark:border-surface-300">
                    <UserPopover />
                    <ThemeToggle compact />
                </div>
            </div>

            <MobileDrawer open={mobileOpen} onClose={() => setMobileOpen(false)} />
        </div>
    );
}
