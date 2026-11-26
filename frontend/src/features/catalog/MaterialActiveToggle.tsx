import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { Button } from '../../components/ui/Button';
import { errorMessage } from '../../lib/errors';

export interface MaterialActiveToggleProps {
    materialId: string;
    scope: 'global' | 'branch';
    isActive: boolean;
    branchId?: string;
    branchName?: string;
    materialName?: string;
}

export function MaterialActiveToggle({
    materialId,
    scope,
    isActive,
    branchId,
    branchName,
    materialName,
}: MaterialActiveToggleProps) {
    const queryClient = useQueryClient();
    const [error, setError] = useState<string | null>(null);
    const action = isActive ? 'Nonaktifkan' : 'Aktifkan';
    const label = scope === 'global' ? 'global' : (branchName ?? 'cabang');
    const text = scope === 'global' ? `${action} global` : action;

    const toggle = useMutation({
        mutationFn: () =>
            scope === 'global'
                ? api.admin.setMaterialActive(materialId, { is_active: !isActive })
                : api.admin.setMaterialBranchAvailability(materialId, {
                      branch_id: branchId,
                      is_active: !isActive,
                  }),
        onSuccess: () => {
            setError(null);
            void queryClient.invalidateQueries({ queryKey: ['materials'] });
            void queryClient.invalidateQueries({ queryKey: ['owner', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['quote-options'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
        },
        onError: (mutationError) => setError(errorMessage(mutationError)),
    });

    return (
        <div className="space-y-1">
            <Button
                size="sm"
                variant="secondary"
                aria-label={`${action} ${label}${materialName ? `: ${materialName}` : ''}`}
                loading={toggle.isPending}
                onClick={() => {
                    setError(null);
                    toggle.mutate();
                }}
            >
                {text}
            </Button>
            {error ? (
                <p role="alert" className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
