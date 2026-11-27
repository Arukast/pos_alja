import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ActivityLog } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { roleLabel, usernameLabel } from '../../lib/labels';
import { AuditDetailModal } from './AuditDetailModal';

export function AuditPage() {
    const [search, setSearch] = useState('');
    const [sort, setSort] = useState('created_at');
    const [direction, setDirection] = useState('desc');
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState<ActivityLog | null>(null);
    const [detailOpen, setDetailOpen] = useState(false);

    const query = useQuery({
        queryKey: ['audit-logs', search, sort, direction, page],
        queryFn: () =>
            api.auditLogs({
                search,
                sort,
                direction,
                page,
            }),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Analitik Pemilik"
                title="Jejak Audit"
                description="Catatan perubahan penting pada sistem yang dapat dicari."
            />

            <Card>
                <CardBody>
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="min-w-0 sm:flex-1">
                            <Input
                                label="Cari"
                                placeholder="Cari pengguna, aksi, deskripsi"
                                value={search}
                                onChange={(event) => {
                                    setSearch(event.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        <div className="sm:w-48">
                            <Select
                                label="Urutkan"
                                value={sort}
                                onChange={(event) => setSort(event.target.value)}
                            >
                                <option value="created_at">Terbaru</option>
                                <option value="username">Nama Pengguna</option>
                                <option value="role">Peran</option>
                                <option value="action">Aksi</option>
                            </Select>
                        </div>
                        <div className="sm:w-40">
                            <Select
                                label="Arah"
                                value={direction}
                                onChange={(event) => setDirection(event.target.value)}
                            >
                                <option value="desc">Menurun</option>
                                <option value="asc">Menaik</option>
                            </Select>
                        </div>
                    </div>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Jejak audit gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : query.data ? (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell className="whitespace-nowrap">Waktu</TableHeaderCell>
                                        <TableHeaderCell>Pengguna</TableHeaderCell>
                                        <TableHeaderCell>Peran</TableHeaderCell>
                                        <TableHeaderCell>Aksi</TableHeaderCell>
                                        <TableHeaderCell>Deskripsi</TableHeaderCell>
                                        <TableHeaderCell>Detail</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.data.length === 0 ? (
                                        <TableEmpty colSpan={6}>Tidak ada entri jejak audit yang cocok.</TableEmpty>
                                    ) : (
                                        query.data.data.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatDate(log.created_at)}
                                                </TableCell>
                                                <TableCell>{usernameLabel(log.username)}</TableCell>
                                                <TableCell>{roleLabel(log.role)}</TableCell>
                                                <TableCell>{log.action}</TableCell>
                                                <TableCell>{log.description}</TableCell>
                                                <TableCell>
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        aria-label={`Lihat detail ${log.action} oleh ${usernameLabel(log.username)}`}
                                                        onClick={() => {
                                                            setSelected(log);
                                                            setDetailOpen(true);
                                                        }}
                                                    >
                                                        Lihat
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={query.data.meta.current_page}
                                lastPage={query.data.meta.last_page}
                                total={query.data.meta.total}
                                perPage={query.data.meta.per_page}
                                onPageChange={setPage}
                            />
                        </>
                    ) : null}
                </CardBody>
            </Card>

            <AuditDetailModal
                log={selected}
                open={detailOpen}
                onClose={() => setDetailOpen(false)}
            />
        </div>
    );
}
