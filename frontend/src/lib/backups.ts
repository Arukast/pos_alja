import type { Backup } from '../api/types';

const byteUnits = ['B', 'KB', 'MB', 'GB', 'TB'];

export type BackupTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info';

export const MANUAL_LOCK_MS = 30 * 60_000;
export const POLL_LIMIT_MS = 2 * 60_000;
export const POLL_INTERVAL_MS = 5_000;

export interface RetentionBounds {
    min: number;
    max: number;
}

export const retentionBounds = {
    local_daily_days: { min: 7, max: 90 },
    local_weekly_weeks: { min: 4, max: 52 },
    local_monthly_months: { min: 6, max: 24 },
    local_yearly_years: { min: 1, max: 10 },
    remote_daily_days: { min: 7, max: 90 },
    remote_weekly_weeks: { min: 4, max: 52 },
    remote_monthly_months: { min: 6, max: 24 },
    remote_yearly_years: { min: 1, max: 10 },
} satisfies Record<string, RetentionBounds>;

export type RetentionKey = keyof typeof retentionBounds;

const statusLabels: Record<string, string> = {
    queued: 'Menunggu',
    running: 'Berjalan',
    success: 'Berhasil',
    partial: 'Sebagian',
    failed: 'Gagal',
};

const statusTones: Record<string, BackupTone> = {
    queued: 'neutral',
    running: 'info',
    success: 'success',
    partial: 'warning',
    failed: 'danger',
};

const destinationLabels: Record<string, string> = {
    local: 'Lokal',
    remote: 'R2',
    both: 'Keduanya',
};

const triggerLabels: Record<string, string> = {
    manual: 'Manual',
    scheduled: 'Terjadwal',
};

export function formatBytes(bytes: number | null): string {
    if (bytes === null || !Number.isFinite(bytes) || bytes < 0) {
        return '-';
    }

    if (bytes === 0) {
        return '0 B';
    }

    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < byteUnits.length - 1) {
        value /= 1024;
        unit += 1;
    }

    const formatted = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value);

    return `${formatted} ${byteUnits[unit]}`;
}

export function backupStatusLabel(status: string): string {
    return statusLabels[status] ?? status;
}

export function backupStatusTone(status: string): BackupTone {
    return statusTones[status] ?? 'neutral';
}

export function backupDestinationLabel(destination: string): string {
    return destinationLabels[destination] ?? destination;
}

export function backupTriggerLabel(trigger: string): string {
    return triggerLabels[trigger] ?? trigger;
}

export function formatBackupTime(value: string | null): string {
    if (value === null || value.trim() === '') {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export function backupDisplayName(filename: string | null, createdAt: string): string {
    return filename ?? formatBackupTime(createdAt);
}

export function isBackupStale(backup: Backup, now = Date.now()): boolean {
    const anchor = backup.started_at ?? backup.created_at;

    return backup.status === 'running' && ageMs(anchor, now) > MANUAL_LOCK_MS;
}

export function isBackupActive(status: string): boolean {
    return status === 'queued' || status === 'running';
}

function activeBackups(rows: Backup[]): Backup[] {
    return rows.filter((row) => isBackupActive(row.status));
}

function ageMs(value: string, now: number): number {
    const time = new Date(value).getTime();

    return Number.isNaN(time) ? Number.POSITIVE_INFINITY : now - time;
}

/** Poll while the newest active row is at most two minutes old. */
export function shouldPoll(rows: Backup[], now = Date.now()): boolean {
    return activeBackups(rows).some((row) => ageMs(row.created_at, now) <= POLL_LIMIT_MS);
}

/** Warn only when every active row has been idle past the poll window. */
export function workerOverdue(rows: Backup[], now = Date.now()): boolean {
    const active = activeBackups(rows);

    return active.length > 0 && active.every((row) => ageMs(row.created_at, now) > POLL_LIMIT_MS);
}

/** Any queued/running row under the 30-minute manual lock blocks a new run. */
export function manualBlocked(rows: Backup[], now = Date.now()): boolean {
    return activeBackups(rows).some((row) => ageMs(row.created_at, now) < MANUAL_LOCK_MS);
}

export function validateRetention(values: Record<RetentionKey, string>): Record<string, string> {
    const errors: Record<string, string> = {};

    for (const key of Object.keys(retentionBounds) as RetentionKey[]) {
        const raw = values[key].trim();
        const value = Number(raw);
        const { min, max } = retentionBounds[key];

        if (raw === '' || !Number.isInteger(value) || value < min || value > max) {
            errors[key] = `Isi angka bulat antara ${min} dan ${max}.`;
        }
    }

    return errors;
}
