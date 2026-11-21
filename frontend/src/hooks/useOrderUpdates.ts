import { useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';

import { useAuth } from '../auth/useAuth';
import { echo } from '../lib/echo';

export function useOrderUpdates(): void {
    const { user } = useAuth();
    const queryClient = useQueryClient();

    useEffect(() => {
        const client = echo;

        if (!client || !user) {
            return;
        }

        const channel =
            user.role === 'owner'
                ? 'owners'
                : user.branch
                  ? `branch.${user.branch.id}`
                  : null;

        if (!channel) {
            return;
        }

        client.private(channel).listen('.OrderUpdated', () => {
            void queryClient.invalidateQueries();
        });

        return () => {
            client.leave(channel);
        };
    }, [user, queryClient]);
}
