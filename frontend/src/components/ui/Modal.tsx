import { X } from 'lucide-react';
import { useEffect, useId, useRef, type ReactNode } from 'react';

import { cn } from '../../lib/cn';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

const sizes: Record<ModalSize, string> = {
    sm: 'max-w-sm',
    md: 'max-w-[560px]',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

export function Modal({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    size = 'md',
}: {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
    size?: ModalSize;
}) {
    const dialogRef = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    const descriptionId = useId();

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        }

        if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        const handleCancel = (event: Event) => {
            event.preventDefault();
            onClose();
        };

        dialog.addEventListener('cancel', handleCancel);

        return () => dialog.removeEventListener('cancel', handleCancel);
    }, [onClose]);

    return (
        <dialog
            ref={dialogRef}
            aria-labelledby={titleId}
            aria-describedby={description ? descriptionId : undefined}
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
            className={cn(
                'm-auto max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] overflow-y-auto rounded-2xl border border-light-border bg-light-surface p-0 text-light-text shadow-card backdrop:bg-brand-dark/55 dark:backdrop:bg-black/70 dark:border-surface-300 dark:bg-surface-700 dark:text-gray-100 dark:shadow-none',
                sizes[size]
            )}
        >
            <div className="flex items-start justify-between gap-3 border-b border-light-border px-5 py-4 dark:border-surface-300">
                <div>
                    <h2 id={titleId} className="text-base font-semibold">
                        {title}
                    </h2>
                    {description ? (
                        <p id={descriptionId} className="mt-0.5 text-sm text-light-muted dark:text-gray-400">
                            {description}
                        </p>
                    ) : null}
                </div>
                <button
                    type="button"
                    aria-label="Tutup dialog"
                    className="rounded-lg p-1 text-light-muted transition hover:bg-light-surface-2 hover:text-light-text dark:text-gray-400 dark:hover:bg-surface-500 dark:hover:text-gray-100"
                    onClick={onClose}
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
            <div className="max-h-[65vh] overflow-y-auto px-5 py-5">{children}</div>
            {footer ? (
                <div className="flex flex-wrap justify-end gap-2 border-t border-light-border px-5 py-4 dark:border-surface-300">
                    {footer}
                </div>
            ) : null}
        </dialog>
    );
}
