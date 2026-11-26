import { describe, expect, it } from 'vitest';

import type { Backup } from '../api/types';
import {
    backupDestinationLabel,
    backupStatusLabel,
    backupStatusTone,
    backupTriggerLabel,
    formatBackupTime,
    formatBytes,
    MANUAL_LOCK_MS,
    manualBlocked,
    POLL_LIMIT_MS,
    shouldPoll,
    validateRetention,
    workerOverdue,
} from './backups';

const NOW = new Date('2026-11-26T12:00:00.000Z').getTime();

function backupAt(ageMs: number, overrides: Partial<Backup> = {}): Backup {
    return {
        id: 'backup-1',
        trigger: 'manual',
        destination: 'local',
        status: 'queued',
        filename: 'backup-2026-11-26.sql.gz',
        size_bytes: null,
        local_available: false,
        remote_available: false,
        is_pinned: false,
        error: null,
        user: null,
        started_at: null,
        finished_at: null,
        created_at: new Date(NOW - ageMs).toISOString(),
        meta: null,
        ...overrides,
    };
}


describe('formatBytes', () => {
    it('formats zero, bytes, and kibibyte values', () => {
        expect(formatBytes(0)).toBe('0 B');
        expect(formatBytes(999)).toBe('999 B');
        expect(formatBytes(1536)).toBe('1,5 KB');
    });

    it('formats gigabyte values', () => {
        expect(formatBytes(1.5 * 1024 ** 3)).toBe('1,5 GB');
    });

    it('returns a dash for null and negative values', () => {
        expect(formatBytes(null)).toBe('-');
        expect(formatBytes(-1)).toBe('-');
    });
});

describe('backupStatusLabel', () => {
    it('labels every stored status', () => {
        expect(backupStatusLabel('queued')).toBe('Menunggu');
        expect(backupStatusLabel('running')).toBe('Berjalan');
        expect(backupStatusLabel('success')).toBe('Berhasil');
        expect(backupStatusLabel('partial')).toBe('Sebagian');
        expect(backupStatusLabel('failed')).toBe('Gagal');
    });

    it('falls back to the input for an unknown status', () => {
        expect(backupStatusLabel('paused')).toBe('paused');
    });
});

describe('backupStatusTone', () => {
    it('maps every stored status to a badge tone', () => {
        expect(backupStatusTone('queued')).toBe('neutral');
        expect(backupStatusTone('running')).toBe('info');
        expect(backupStatusTone('success')).toBe('success');
        expect(backupStatusTone('partial')).toBe('warning');
        expect(backupStatusTone('failed')).toBe('danger');
    });

    it('falls back to neutral for an unknown status', () => {
        expect(backupStatusTone('paused')).toBe('neutral');
    });
});

describe('backupDestinationLabel', () => {
    it('labels every stored destination', () => {
        expect(backupDestinationLabel('local')).toBe('Lokal');
        expect(backupDestinationLabel('remote')).toBe('R2');
        expect(backupDestinationLabel('both')).toBe('Keduanya');
    });

    it('falls back to the input for an unknown destination', () => {
        expect(backupDestinationLabel('tape')).toBe('tape');
    });
});

describe('backupTriggerLabel', () => {
    it('labels every stored trigger', () => {
        expect(backupTriggerLabel('manual')).toBe('Manual');
        expect(backupTriggerLabel('scheduled')).toBe('Terjadwal');
    });

    it('falls back to the input for an unknown trigger', () => {
        expect(backupTriggerLabel('cron')).toBe('cron');
    });
});

describe('formatBackupTime', () => {
    it('formats a timestamp in Indonesian with a short time', () => {
        expect(formatBackupTime('2026-11-26T14:30:00')).toBe('26 Nov 2026, 14.30');
    });

    it('returns a dash for null and invalid values', () => {
        expect(formatBackupTime(null)).toBe('-');
        expect(formatBackupTime('bukan-tanggal')).toBe('-');
    });
});

describe('shouldPoll', () => {
    it('polls while the newest active row is at most two minutes old', () => {
        expect(shouldPoll([backupAt(POLL_LIMIT_MS)], NOW)).toBe(true);
        expect(shouldPoll([backupAt(POLL_LIMIT_MS + 1)], NOW)).toBe(false);
    });

    it('polls a mixed list when a fresh row exists next to a stale one', () => {
        expect(shouldPoll([backupAt(31 * 60_000), backupAt(10_000)], NOW)).toBe(true);
    });

    it('does not poll without an active row', () => {
        expect(shouldPoll([backupAt(10_000, { status: 'success' })], NOW)).toBe(false);
        expect(shouldPoll([], NOW)).toBe(false);
    });
});

describe('workerOverdue', () => {
    it('warns only when every active row is older than two minutes', () => {
        expect(workerOverdue([backupAt(POLL_LIMIT_MS + 1)], NOW)).toBe(true);
        expect(workerOverdue([backupAt(POLL_LIMIT_MS)], NOW)).toBe(false);
    });

    it('does not warn for a mixed stale and fresh list', () => {
        expect(workerOverdue([backupAt(31 * 60_000), backupAt(10_000)], NOW)).toBe(false);
    });

    it('does not warn without an active row', () => {
        expect(workerOverdue([backupAt(31 * 60_000, { status: 'failed' })], NOW)).toBe(false);
        expect(workerOverdue([], NOW)).toBe(false);
    });
});

describe('manualBlocked', () => {
    it('blocks while an active row is under the thirty-minute lock', () => {
        expect(manualBlocked([backupAt(MANUAL_LOCK_MS - 1)], NOW)).toBe(true);
        expect(manualBlocked([backupAt(MANUAL_LOCK_MS)], NOW)).toBe(false);
    });

    it('blocks for a fresh scheduled row as well as a manual one', () => {
        expect(manualBlocked([backupAt(10_000, { trigger: 'scheduled' })], NOW)).toBe(true);
    });

    it('blocks a mixed list with one fresh active row', () => {
        expect(manualBlocked([backupAt(31 * 60_000), backupAt(10_000)], NOW)).toBe(true);
    });

    it('does not block for inactive rows', () => {
        expect(manualBlocked([backupAt(10_000, { status: 'partial' })], NOW)).toBe(false);
    });
});

describe('validateRetention', () => {
    const valid = {
        local_daily_days: '7',
        local_weekly_weeks: '4',
        local_monthly_months: '6',
        local_yearly_years: '1',
        remote_daily_days: '90',
        remote_weekly_weeks: '52',
        remote_monthly_months: '24',
        remote_yearly_years: '10',
    };

    it('accepts the inclusive boundaries', () => {
        expect(validateRetention(valid)).toEqual({});
    });

    it('rejects values outside the backend bounds', () => {
        expect(validateRetention({ ...valid, local_daily_days: '6' })).toEqual({
            local_daily_days: 'Isi angka bulat antara 7 dan 90.',
        });
        expect(validateRetention({ ...valid, remote_yearly_years: '11' })).toEqual({
            remote_yearly_years: 'Isi angka bulat antara 1 dan 10.',
        });
    });

    it('rejects empty and fractional values', () => {
        expect(validateRetention({ ...valid, local_weekly_weeks: '' }).local_weekly_weeks).toBe(
            'Isi angka bulat antara 4 dan 52.'
        );
        expect(validateRetention({ ...valid, remote_monthly_months: '6.5' }).remote_monthly_months).toBe(
            'Isi angka bulat antara 6 dan 24.'
        );
    });
});
