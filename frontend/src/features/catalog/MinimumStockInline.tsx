import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useEffect, useId, useRef, useState } from 'react';

import { api } from '../../api/queries';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { errorMessage } from '../../lib/errors';
import { quantityWithUnit } from '../../lib/units';

export interface MinimumStockInlineProps {
    materialId: string;
    branchId?: string;
    value: string | number | null;
    unit: string | null;
    label: string;
}

export function MinimumStockInline({
    materialId,
    branchId,
    value,
    unit,
    label,
}: MinimumStockInlineProps) {
    const queryClient = useQueryClient();
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');
    const [error, setError] = useState<string | null>(null);
    const triggerRef = useRef<HTMLDivElement>(null);
    const restoreFocus = useRef(false);
    const errorId = useId();

    useEffect(() => {
        if (!editing && restoreFocus.current) {
            restoreFocus.current = false;
            triggerRef.current?.querySelector('button')?.focus();
        }
    }, [editing]);

    const closeEditor = () => {
        restoreFocus.current = true;
        setEditing(false);
        setError(null);
    };

    const save = useMutation({
        mutationFn: () =>
            api.admin.setMaterialMinimumStock(
                materialId,
                branchId === undefined
                    ? { min_stock: Number(draft) }
                    : { branch_id: branchId, min_stock: Number(draft) }
            ),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['owner', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['materials'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            closeEditor();
        },
        onError: (mutationError) => setError(errorMessage(mutationError)),
    });

    if (!editing) {
        return (
            <div
                ref={triggerRef}
                className="inline-flex items-center gap-1.5 whitespace-nowrap tabular-nums"
            >
                <span>{quantityWithUnit(value, unit)}</span>
                <Button
                    size="sm"
                    variant="ghost"
                    aria-label={`Ubah stok minimum ${label}`}
                    onClick={() => {
                        setDraft(value === null ? '0' : String(value));
                        setError(null);
                        setEditing(true);
                    }}
                >
                    Ubah
                </Button>
            </div>
        );
    }

    return (
        <div className="inline-block space-y-1 text-left">
            <div className="flex flex-wrap items-center gap-1.5 whitespace-nowrap">
                <div className="w-24">
                    <Input
                        type="number"
                        min="0"
                        step="any"
                        autoFocus
                        aria-label={`Stok minimum ${label}`}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={error ? errorId : undefined}
                        value={draft}
                        onChange={(event) => {
                            setDraft(event.target.value);
                            setError(null);
                        }}
                    />
                </div>
                <Button
                    size="sm"
                    loading={save.isPending}
                    disabled={draft.trim() === '' || Number(draft) < 0}
                    onClick={() => save.mutate()}
                >
                    Simpan
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    disabled={save.isPending}
                    onClick={closeEditor}
                >
                    Batal
                </Button>
            </div>
            {error ? (
                <p
                    id={errorId}
                    role="alert"
                    className="text-xs text-red-600 dark:text-red-400"
                >
                    {error}
                </p>
            ) : null}
        </div>
    );
}
