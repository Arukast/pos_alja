import { X } from 'lucide-react';

import { cn } from '../../lib/cn';

export type ToastTone = 'success' | 'error' | 'info';

const tones: Record<ToastTone, string> = {
    success:
        'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
    error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200',
    info: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200',
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
            className={cn(
                'fixed right-4 bottom-4 z-50 flex max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg',
                tones[tone]
            )}
        >
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
    );
}
