import { Menu } from 'lucide-react';
import { useState } from 'react';
import { Outlet } from 'react-router';

import { MobileDrawer } from './MobileDrawer';
import { Sidebar } from './Sidebar';
import { ThemeToggle } from './ThemeToggle';
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
        <div className="bg-light-bg text-light-text dark:bg-surface-900 flex h-screen flex-col overflow-hidden dark:text-gray-100">
            <div className="brand-stripe-bar shrink-0" aria-hidden="true" />

            <div className="flex min-h-0 flex-1">
                <Sidebar collapsed={collapsed} onToggleCollapsed={toggleCollapsed} />

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="border-light-border flex h-14 shrink-0 items-center justify-between border-b px-4 lg:hidden dark:border-surface-300">
                        <button
                            type="button"
                            aria-label="Buka navigasi"
                            className="text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:hover:bg-surface-600 rounded-xl p-2 dark:text-gray-400 dark:hover:text-gray-100"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        <div className="flex min-w-0 items-center gap-2">
                            <img src="/images/logo-alja-mark.png" alt="" className="h-7 w-7 shrink-0 object-contain" />
                            <span className="text-sm font-bold">Alja POS</span>
                        </div>
                        <ThemeToggle compact />
                    </header>

                    <main className="min-h-0 flex-1 overflow-y-auto">
                        <div className="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
                            <Outlet />
                        </div>
                    </main>
                </div>
            </div>

            <MobileDrawer open={mobileOpen} onClose={() => setMobileOpen(false)} />
        </div>
    );
}
