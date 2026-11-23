import { X } from 'lucide-react';

import { cn } from '../../lib/cn';

export type ToastTone = 'success' | 'error' | 'info';

const tones: Record<ToastTone, string> = {
    success: 'text-emerald-800 dark:text-emerald-200',
    error: 'text-red-800 dark:text-red-200',
    info: 'text-sky-800 dark:text-sky-200',
};

const bars: Record<ToastTone, string> = {
    success: 'bg-emerald-600',
    error: 'bg-red-600',
    info: 'bg-sky-600',
};

export function Toast({
    message,
    tone = 'success',
    onClose,
}: {
    message: string;
    tone?: ToastTone;
    onClose?: () => void;
}) {
    return (
        <div
            role={tone === 'error' ? 'alert' : 'status'}
            className="fixed right-4 bottom-4 left-4 z-50 sm:left-auto sm:max-w-sm"
        >
            <div
                className={cn(
                    'relative flex items-start gap-3 overflow-hidden rounded-xl border border-light-border bg-light-surface px-4 py-3 text-sm shadow-lg dark:border-surface-300 dark:bg-surface-700',
                    tones[tone]
                )}
            >
                <span aria-hidden className={cn('absolute inset-y-0 left-0 w-[3px]', bars[tone])} />
                <p className="flex-1">{message}</p>
                {onClose ? (
                    <button
                        type="button"
                        aria-label="Tutup notifikasi"
                        className="rounded p-0.5 opacity-70 transition hover:opacity-100"
                        onClick={onClose}
                    >
                        <X className="h-4 w-4" />
                    </button>
                ) : null}
            </div>
        </div>
    );
}
