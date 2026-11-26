import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useRef, useState } from 'react';

import { api } from '../../api/queries';
import type { Backup, BackupSettings } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast, type ToastTone } from '../../components/ui/Toast';
import {
    backupDestinationLabel,
    backupStatusLabel,
    backupStatusTone,
    backupTriggerLabel,
    formatBackupTime,
    formatBytes,
    isBackupActive,
    MANUAL_LOCK_MS,
    manualBlocked,
    POLL_INTERVAL_MS,
    shouldPoll,
    validateRetention,
    workerOverdue,
} from '../../lib/backups';
import { cn } from '../../lib/cn';
import { errorMessage, mapApiErrors } from '../../lib/errors';

const downloadLinkClass =
    'focus-visible:ring-brand-fill focus-visible:ring-offset-light-bg dark:focus-visible:ring-offset-surface-900 inline-flex h-8 items-center justify-center rounded-xl border border-light-muted bg-light-surface px-3 text-xs font-semibold whitespace-nowrap text-light-text transition hover:bg-light-surface-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none dark:bg-surface-600 dark:text-gray-100 dark:hover:bg-surface-500';

function ageMs(value: string): number {
    const time = new Date(value).getTime();

    return Number.isNaN(time) ? Number.POSITIVE_INFINITY : Date.now() - time;
}

function percentLabel(value: number): string {
    return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value)}%`;
}

interface SettingsForm {
    local_enabled: boolean;
    local_time: string;
    remote_enabled: boolean;
    remote_time: string;
    local_daily_days: string;
    local_weekly_weeks: string;
    local_monthly_months: string;
    local_yearly_years: string;
    remote_daily_days: string;
    remote_weekly_weeks: string;
    remote_monthly_months: string;
    remote_yearly_years: string;
}

function formFromSettings(settings: BackupSettings): SettingsForm {
    return {
        local_enabled: settings.local_enabled,
        local_time: settings.local_time,
        remote_enabled: settings.remote_enabled,
        remote_time: settings.remote_time,
        local_daily_days: String(settings.local_daily_days),
        local_weekly_weeks: String(settings.local_weekly_weeks),
        local_monthly_months: String(settings.local_monthly_months),
        local_yearly_years: String(settings.local_yearly_years),
        remote_daily_days: String(settings.remote_daily_days),
        remote_weekly_weeks: String(settings.remote_weekly_weeks),
        remote_monthly_months: String(settings.remote_monthly_months),
        remote_yearly_years: String(settings.remote_yearly_years),
    };
}

function SettingsCard({
    settings,
    onNotice,
}: {
    settings: BackupSettings;
    onNotice: (message: string, tone: ToastTone) => void;
}) {
    const queryClient = useQueryClient();
    const [form, setForm] = useState<SettingsForm>(() => formFromSettings(settings));
    const [errors, setErrors] = useState<Record<string, string>>({});

    const update = <K extends keyof SettingsForm>(key: K, value: SettingsForm[K]): void => {
        setForm((current) => ({ ...current, [key]: value }));
        setErrors((current) => {
            if (!(key in current)) {
                return current;
            }

            const next = { ...current };
            delete next[key];

            return next;
        });
    };

    const save = useMutation({
        mutationFn: () =>
            api.updateBackupSettings({
                local_enabled: form.local_enabled,
                local_time: form.local_time,
                remote_enabled: form.remote_enabled,
                remote_time: form.remote_time,
                local_daily_days: Number(form.local_daily_days),
                local_weekly_weeks: Number(form.local_weekly_weeks),
                local_monthly_months: Number(form.local_monthly_months),
                local_yearly_years: Number(form.local_yearly_years),
                remote_daily_days: Number(form.remote_daily_days),
                remote_weekly_weeks: Number(form.remote_weekly_weeks),
                remote_monthly_months: Number(form.remote_monthly_months),
                remote_yearly_years: Number(form.remote_yearly_years),
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['backups'] });
            void queryClient.invalidateQueries({ queryKey: ['backup-settings'] });
            setErrors({});
            onNotice('Pengaturan backup disimpan.', 'success');
        },
        onError: (error) => {
            const fieldErrors = mapApiErrors(error);
            setErrors(fieldErrors);

            if (Object.keys(fieldErrors).length === 0) {
                onNotice(errorMessage(error), 'error');
            }
        },
    });

    const submit = (): void => {
        const fieldErrors = validateRetention(form);
        setErrors(fieldErrors);

        if (Object.keys(fieldErrors).length > 0) {
            return;
        }

        save.mutate();
    };

    const usageTone =
        settings.remote_usage_percent >= 100
            ? 'text-red-700 dark:text-red-300'
            : settings.remote_usage_percent >= 80
              ? 'text-amber-700 dark:text-amber-300'
              : 'text-light-text dark:text-gray-100';

    return (
        <Card>
            <CardHeader>
                <div>
                    <h2 className="text-light-text text-sm font-semibold dark:text-gray-100">
                        Pengaturan Backup
                    </h2>
                    <p className="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                        Jadwal dan retensi berlaku pada tick berikutnya.
                    </p>
                </div>
            </CardHeader>
            <CardBody className="space-y-6">
                <div className="space-y-1">
                    <p className={cn('text-sm font-semibold', usageTone)}>
                        Penggunaan R2: {formatBytes(settings.remote_usage_bytes)} dari{' '}
                        {formatBytes(settings.remote_quota_bytes)} (
                        {percentLabel(settings.remote_usage_percent)})
                    </p>
                    <p className="text-light-muted text-xs dark:text-gray-400">
                        Perkiraan dari baris backup yang tercatat. {settings.pinned_count} backup
                        disematkan.
                    </p>
                </div>

                {!settings.remote_enabled ? (
                    <p className="rounded-xl bg-sky-50 px-3 py-2 text-xs text-sky-800 dark:bg-sky-500/10 dark:text-sky-300">
                        R2 nonaktif, backup lokal saja.
                    </p>
                ) : !settings.remote_configured ? (
                    <p
                        role="status"
                        className="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        R2 belum dikonfigurasi. Backup remote akan gagal sampai kredensial diatur
                        operator.
                    </p>
                ) : null}

                <div className="grid gap-6 sm:grid-cols-2">
                    <fieldset className="min-w-0 space-y-3">
                        <legend className="text-light-text text-xs font-semibold uppercase tracking-wider dark:text-gray-200">
                            Backup Lokal
                        </legend>
                        <Checkbox
                            label="Aktifkan backup lokal harian"
                            checked={form.local_enabled}
                            error={errors.local_enabled}
                            onChange={(event) => update('local_enabled', event.target.checked)}
                        />
                        <Input
                            label="Waktu lokal"
                            type="time"
                            value={form.local_time}
                            error={errors.local_time}
                            onChange={(event) => update('local_time', event.target.value)}
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <Input
                                label="Harian (hari)"
                                type="number"
                                min="7"
                                max="90"
                                value={form.local_daily_days}
                                error={errors.local_daily_days}
                                onChange={(event) =>
                                    update('local_daily_days', event.target.value)
                                }
                            />
                            <Input
                                label="Mingguan (minggu)"
                                type="number"
                                min="4"
                                max="52"
                                value={form.local_weekly_weeks}
                                error={errors.local_weekly_weeks}
                                onChange={(event) =>
                                    update('local_weekly_weeks', event.target.value)
                                }
                            />
                            <Input
                                label="Bulanan (bulan)"
                                type="number"
                                min="6"
                                max="24"
                                value={form.local_monthly_months}
                                error={errors.local_monthly_months}
                                onChange={(event) =>
                                    update('local_monthly_months', event.target.value)
                                }
                            />
                            <Input
                                label="Tahunan (tahun)"
                                type="number"
                                min="1"
                                max="10"
                                value={form.local_yearly_years}
                                error={errors.local_yearly_years}
                                onChange={(event) =>
                                    update('local_yearly_years', event.target.value)
                                }
                            />
                        </div>
                    </fieldset>

                    <fieldset className="min-w-0 space-y-3">
                        <legend className="text-light-text text-xs font-semibold uppercase tracking-wider dark:text-gray-200">
                            Backup R2
                        </legend>
                        <Checkbox
                            label="Aktifkan backup R2 harian"
                            checked={form.remote_enabled}
                            error={errors.remote_enabled}
                            onChange={(event) => update('remote_enabled', event.target.checked)}
                        />
                        <Input
                            label="Waktu R2"
                            type="time"
                            value={form.remote_time}
                            error={errors.remote_time}
                            onChange={(event) => update('remote_time', event.target.value)}
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <Input
                                label="Harian (hari)"
                                type="number"
                                min="7"
                                max="90"
                                value={form.remote_daily_days}
                                error={errors.remote_daily_days}
                                onChange={(event) =>
                                    update('remote_daily_days', event.target.value)
                                }
                            />
                            <Input
                                label="Mingguan (minggu)"
                                type="number"
                                min="4"
                                max="52"
                                value={form.remote_weekly_weeks}
                                error={errors.remote_weekly_weeks}
                                onChange={(event) =>
                                    update('remote_weekly_weeks', event.target.value)
                                }
                            />
                            <Input
                                label="Bulanan (bulan)"
                                type="number"
                                min="6"
                                max="24"
                                value={form.remote_monthly_months}
                                error={errors.remote_monthly_months}
                                onChange={(event) =>
                                    update('remote_monthly_months', event.target.value)
                                }
                            />
                            <Input
                                label="Tahunan (tahun)"
                                type="number"
                                min="1"
                                max="10"
                                value={form.remote_yearly_years}
                                error={errors.remote_yearly_years}
                                onChange={(event) =>
                                    update('remote_yearly_years', event.target.value)
                                }
                            />
                        </div>
                    </fieldset>
                </div>

                <div className="flex flex-col gap-3 border-t border-light-border pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-surface-300">
                    <p className="text-light-muted text-xs dark:text-gray-400">
                        Kredensial R2 diatur operator melalui .env.
                    </p>
                    <Button loading={save.isPending} onClick={submit}>
                        Simpan Pengaturan
                    </Button>
                </div>
            </CardBody>
        </Card>
    );
}

function StatusCell({ backup }: { backup: Backup }) {
    const runningStale = backup.status === 'running' && ageMs(backup.created_at) > MANUAL_LOCK_MS;

    return (
        <>
            <div className="flex flex-wrap items-center gap-1.5">
                <Badge tone={backupStatusTone(backup.status)}>
                    {backupStatusLabel(backup.status)}
                </Badge>
                {runningStale ? <Badge tone="warning">Mungkin macet</Badge> : null}
                {backup.meta?.remote_skipped ? <Badge tone="info">R2 dilewati</Badge> : null}
            </div>
            {backup.meta?.retention_error ? (
                <p className="mt-1 max-w-[18rem] text-xs text-amber-700 dark:text-amber-300">
                    Retensi gagal: {backup.meta.retention_error}
                </p>
            ) : null}
            {backup.error ? (
                <p className="mt-1 max-w-[18rem] text-xs break-words text-red-600 dark:text-red-400">
                    {backup.error}
                </p>
            ) : null}
        </>
    );
}

export function BackupPage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [notice, setNotice] = useState<{ message: string; tone: ToastTone } | null>(null);

    const backups = useQuery({
        queryKey: ['backups', page],
        queryFn: () => api.backups({ page }),
        refetchInterval: (query) =>
            shouldPoll(query.state.data?.data ?? []) ? POLL_INTERVAL_MS : false,
    });

    const settings = useQuery({
        queryKey: ['backup-settings'],
        queryFn: () => api.backupSettings(),
    });

    const rows = backups.data?.data ?? [];
    const activeRows = rows.filter((row) => isBackupActive(row.status));
    const hasActiveRows = activeRows.length > 0;
    const previousHasActiveRows = useRef(false);

    // A finished run changes R2 usage, so refresh settings on the active -> idle transition.
    useEffect(() => {
        if (previousHasActiveRows.current && !hasActiveRows) {
            void queryClient.invalidateQueries({ queryKey: ['backup-settings'] });
        }

        previousHasActiveRows.current = hasActiveRows;
    }, [hasActiveRows, queryClient]);

    const notify = (message: string, tone: ToastTone): void => setNotice({ message, tone });

    const create = useMutation({
        mutationFn: () => api.createBackup(),
        onSuccess: (data) => {
            void queryClient.invalidateQueries({ queryKey: ['backups'] });
            void queryClient.invalidateQueries({ queryKey: ['backup-settings'] });
            notify(data.message, 'success');
        },
        onError: (error) => notify(errorMessage(error), 'error'),
    });

    const pin = useMutation({
        mutationFn: ({ id, pinned }: { id: string; pinned: boolean }) =>
            api.pinBackup(id, pinned),
        onSuccess: (data) => {
            void queryClient.invalidateQueries({ queryKey: ['backups'] });
            void queryClient.invalidateQueries({ queryKey: ['backup-settings'] });
            notify(
                data.backup.is_pinned ? 'Backup disematkan.' : 'Sematan backup dilepas.',
                'success'
            );
        },
        onError: (error) => notify(errorMessage(error), 'error'),
    });

    const blocked = manualBlocked(rows);
    const overdue = workerOverdue(rows);

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Administrasi"
                title="Backup Data"
                description="Jalankan backup manual, atur jadwal dan retensi, unduh arsip database."
                actions={
                    <Button
                        loading={create.isPending}
                        disabled={blocked}
                        onClick={() => create.mutate()}
                    >
                        Jalankan Backup
                    </Button>
                }
            />

            {settings.isLoading ? (
                <Card>
                    <div role="status" className="flex items-center justify-center px-5 py-12">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                </Card>
            ) : settings.isError ? (
                <EmptyState
                    title="Pengaturan backup gagal dimuat"
                    description="Periksa koneksi lalu coba lagi."
                    action={
                        <Button variant="secondary" onClick={() => settings.refetch()}>
                            Coba lagi
                        </Button>
                    }
                />
            ) : settings.data ? (
                <SettingsCard settings={settings.data.settings} onNotice={notify} />
            ) : null}

            <Card>
                <CardHeader>
                    <h2 className="text-light-text text-sm font-semibold dark:text-gray-100">
                        Riwayat Backup
                    </h2>
                    {activeRows.length > 0 ? (
                        <p className="text-light-muted text-xs dark:text-gray-400">
                            Memantau {activeRows.length} backup berjalan.
                        </p>
                    ) : null}
                </CardHeader>
                <CardBody>
                    {overdue ? (
                        <p
                            role="status"
                            className="mb-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            Belum ada hasil. Periksa worker.
                        </p>
                    ) : null}

                    {backups.isLoading ? (
                        <div role="status" className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : backups.isError ? (
                        <EmptyState
                            title="Riwayat backup gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => backups.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : backups.data ? (
                        <>
                            <Table ariaLabel="Riwayat backup">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell className="whitespace-nowrap">
                                            Waktu
                                        </TableHeaderCell>
                                        <TableHeaderCell>Jenis</TableHeaderCell>
                                        <TableHeaderCell>Tujuan</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Ukuran</TableHeaderCell>
                                        <TableHeaderCell>Aksi</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableEmpty colSpan={6}>
                                            Belum ada backup. Jalankan backup pertama.
                                        </TableEmpty>
                                    ) : (
                                        rows.map((backup) => (
                                            <TableRow key={backup.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatBackupTime(backup.created_at)}
                                                </TableCell>
                                                <TableCell>
                                                    {backupTriggerLabel(backup.trigger)}
                                                </TableCell>
                                                <TableCell>
                                                    {backupDestinationLabel(backup.destination)}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusCell backup={backup} />
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatBytes(backup.size_bytes)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {backup.local_available ||
                                                        backup.remote_available ? (
                                                            <a
                                                                href={api.backupDownloadUrl(
                                                                    backup.id
                                                                )}
                                                                aria-label={`Unduh backup ${backup.filename} dari ${formatBackupTime(backup.created_at)}`}
                                                                className={downloadLinkClass}
                                                            >
                                                                Unduh
                                                            </a>
                                                        ) : (
                                                            <span className="text-light-muted text-xs whitespace-nowrap dark:text-gray-400">
                                                                Tidak tersedia
                                                            </span>
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            aria-label={`${backup.is_pinned ? 'Lepas sematan' : 'Sematkan'} backup ${backup.filename} dari ${formatBackupTime(backup.created_at)}`}
                                                            aria-pressed={backup.is_pinned}
                                                            loading={
                                                                pin.isPending &&
                                                                pin.variables?.id === backup.id
                                                            }
                                                            onClick={() =>
                                                                pin.mutate({
                                                                    id: backup.id,
                                                                    pinned: !backup.is_pinned,
                                                                })
                                                            }
                                                        >
                                                            {backup.is_pinned ? 'Lepas' : 'Sematkan'}
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={backups.data.meta.current_page}
                                lastPage={backups.data.meta.last_page}
                                total={backups.data.meta.total}
                                perPage={backups.data.meta.per_page}
                                onPageChange={setPage}
                            />
                        </>
                    ) : null}
                </CardBody>
            </Card>

            {notice ? (
                <Toast
                    message={notice.message}
                    tone={notice.tone}
                    onClose={() => setNotice(null)}
                />
            ) : null}
        </div>
    );
}
