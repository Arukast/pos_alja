import { useQuery } from '@tanstack/react-query';

import { api } from '../api/queries';

export function useBranches() {
    return useQuery({
        queryKey: ['branches', 'selector'],
        queryFn: () => api.branches(),
    });
}
