import { Navigate, useSearchParams } from 'react-router';

import { useAuth } from '../auth/useAuth';
import { legacyTarget } from './legacyTargets';

export function LegacyDashboardRedirect() {
    const { user } = useAuth();
    const [searchParams] = useSearchParams();

    return <Navigate to={legacyTarget(user?.role ?? '', searchParams.get('tab') ?? '')} replace />;
}
