import { useQuery } from '@tanstack/react-query';

import { api } from '../api/queries';

export function useBranches(enabled = true) {
    return useQuery({
        queryKey: ['branches', 'selector'],
        queryFn: () => api.branches(),
        enabled,
    });
}
