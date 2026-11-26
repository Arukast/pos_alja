import type { ReactNode } from 'react';

import type { ActivityLog } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Modal } from '../../components/ui/Modal';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '../../components/ui/Table';
import { auditPayloadView } from '../../lib/audit';
import { formatBackupTime } from '../../lib/backups';
import { roleLabel } from '../../lib/labels';

function MetadataRow({ label, children }: { label: string; children: ReactNode }) {
    return (
        <>
            <dt className="text-light-muted dark:text-gray-400">{label}</dt>
            <dd className="min-w-0 font-medium break-words">{children}</dd>
        </>
    );
}

function PayloadSection({ payload }: { payload: ActivityLog['payload'] }) {
    const view = auditPayloadView(payload);

    if (view.kind === 'empty') {
        return <p className="text-light-muted text-sm dark:text-gray-400">Tidak ada payload.</p>;
    }

    if (view.kind === 'comparison') {
        return (
            <Table ariaLabel="Perbandingan payload sebelum dan sesudah">
                <TableHead>
                    <TableRow>
                        <TableHeaderCell>Bidang</TableHeaderCell>
                        <TableHeaderCell>Sebelum</TableHeaderCell>
                        <TableHeaderCell>Sesudah</TableHeaderCell>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {view.rows.map((row) => (
                        <TableRow key={row.key}>
                            <TableCell className="font-medium whitespace-nowrap">{row.key}</TableCell>
                            <TableCell className="min-w-40 break-words">{row.before}</TableCell>
                            <TableCell className="min-w-40 break-words">{row.after}</TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        );
    }

    return (
        <Table ariaLabel="Payload jejak audit">
            <TableHead>
                <TableRow>
                    <TableHeaderCell>Bidang</TableHeaderCell>
                    <TableHeaderCell>Nilai</TableHeaderCell>
                </TableRow>
            </TableHead>
            <TableBody>
                {view.rows.map((row) => (
                    <TableRow key={row.key}>
                        <TableCell className="font-medium whitespace-nowrap">{row.key}</TableCell>
                        <TableCell className="break-words">{row.value}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

export function AuditDetailModal({
    log,
    open,
    onClose,
}: {
    log: ActivityLog | null;
    open: boolean;
    onClose: () => void;
}) {
    if (!log) {
        return null;
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            size="xl"
            title="Detail Jejak Audit"
            footer={
                <Button variant="secondary" onClick={onClose}>
                    Tutup
                </Button>
            }
        >
            <div className="space-y-5">
                <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[7rem_minmax(0,1fr)]">
                    <MetadataRow label="Waktu">{formatBackupTime(log.created_at)}</MetadataRow>
                    <MetadataRow label="Pengguna">
                        {log.username} ({roleLabel(log.role)})
                    </MetadataRow>
                    <MetadataRow label="Aksi">{log.action}</MetadataRow>
                    <MetadataRow label="Model">
                        {log.model_type
                            ? `${log.model_type}${log.model_id ? ` (${log.model_id})` : ''}`
                            : '-'}
                    </MetadataRow>
                    <MetadataRow label="Deskripsi">{log.description}</MetadataRow>
                </dl>

                <section className="space-y-2">
                    <h3 className="text-sm font-semibold">Payload</h3>
                    <PayloadSection payload={log.payload} />
                </section>
            </div>
        </Modal>
    );
}
