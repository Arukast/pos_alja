import { Navigate, Outlet, useLocation } from 'react-router';

import { Spinner } from '../components/ui/Spinner';
import { roleHome } from './roleHome';
import { useAuth } from './useAuth';

export function RequireAuth() {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <Spinner className="text-brand-primary h-6 w-6" />
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    return <Outlet />;
}

export function RequireRole({ roles }: { roles: string[] }) {
    const { user } = useAuth();

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    if (!roles.includes(user.role)) {
        return <Navigate to={roleHome(user.role)} replace />;
    }

    return <Outlet />;
}

export function RoleRedirect() {
    const { user, loading } = useAuth();

    if (loading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <Spinner className="text-brand-primary h-6 w-6" />
            </div>
        );
    }

    return <Navigate to={roleHome(user?.role)} replace />;
}
