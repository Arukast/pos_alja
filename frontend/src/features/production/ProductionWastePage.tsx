import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { formatDate } from '../../lib/dates';
import { errorMessage, mapApiErrors } from '../../lib/errors';

export function ProductionWastePage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [form, setForm] = useState({
        material_id: '',
        machine_id: '',
        designer_id: '',
        quantity: '1',
        dimensions: '',
        notes: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const optionsQuery = useQuery({ queryKey: ['waste', 'options'], queryFn: api.wasteOptions });
    const listQuery = useQuery({ queryKey: ['waste', 'list', page], queryFn: () => api.waste({ page }) });

    const create = useMutation({
        mutationFn: () =>
            api.createWaste({
                ...form,
                quantity: Number(form.quantity),
                machine_id: form.machine_id || null,
                designer_id: form.designer_id || null,
                dimensions: form.dimensions || null,
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['waste'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            void queryClient.invalidateQueries({ queryKey: ['production'] });
            setForm({ ...form, quantity: '1', dimensions: '', notes: '' });
            setErrors({});
            setNotice('Sisa bahan dicatat dan stok dikurangi.');
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const options = optionsQuery.data;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Catat Sisa Bahan"
                description="Catat cetakan gagal dan kurangi stok bahan."
            />

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Catat sisa bahan</h2>
                </CardHeader>
                <CardBody className="space-y-4">
                    {optionsQuery.isLoading ? (
                        <div role="status" className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : optionsQuery.isError ? (
                        <EmptyState
                            title="Pilihan bahan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => optionsQuery.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (options?.materials ?? []).length === 0 ? (
                        <EmptyState
                            title="Belum ada bahan di cabang ini"
                            description="Tambahkan stok bahan lewat inventaris sebelum mencatat sisa."
                        />
                    ) : (
                        <>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                                <Select
                                    label="Bahan"
                                    value={form.material_id}
                                    error={errors.material_id}
                                    onChange={(event) => setForm({ ...form, material_id: event.target.value })}
                                >
                                    <option value="">Pilih bahan</option>
                                    {(options?.materials ?? []).map((material) => (
                                        <option key={material.id} value={material.id}>
                                            {material.name}
                                        </option>
                                    ))}
                                </Select>
                                <Select
                                    label="Mesin"
                                    value={form.machine_id}
                                    error={errors.machine_id}
                                    onChange={(event) => setForm({ ...form, machine_id: event.target.value })}
                                >
                                    <option value="">Tanpa mesin</option>
                                    {(options?.machines ?? []).map((machine) => (
                                        <option key={machine.id} value={machine.id}>
                                            {machine.name}
                                        </option>
                                    ))}
                                </Select>
                                <Select
                                    label="Desainer"
                                    value={form.designer_id}
                                    error={errors.designer_id}
                                    onChange={(event) => setForm({ ...form, designer_id: event.target.value })}
                                >
                                    <option value="">Tanpa desainer</option>
                                    {(options?.designers ?? []).map((designer) => (
                                        <option key={designer.id} value={designer.id}>
                                            {designer.name}
                                        </option>
                                    ))}
                                </Select>
                                <Input
                                    label="Jml"
                                    type="number"
                                    min="0"
                                    value={form.quantity}
                                    error={errors.quantity}
                                    onChange={(event) => setForm({ ...form, quantity: event.target.value })}
                                />
                                <Input
                                    label="Dimensi"
                                    value={form.dimensions}
                                    onChange={(event) => setForm({ ...form, dimensions: event.target.value })}
                                />
                            </div>
                            <Input
                                label="Catatan"
                                value={form.notes}
                                error={errors.notes}
                                onChange={(event) => setForm({ ...form, notes: event.target.value })}
                            />
                            <Button loading={create.isPending} onClick={() => create.mutate()}>
                                Catat Sisa Bahan
                            </Button>
                        </>
                    )}
                </CardBody>
            </Card>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Riwayat sisa bahan</h2>
                    {listQuery.data ? (
                        <span className="bg-light-surface-2 text-light-muted-strong rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums dark:bg-surface-600 dark:text-gray-300">
                            {listQuery.data.meta.total} catatan
                        </span>
                    ) : null}
                </CardHeader>
                <CardBody>
                    {listQuery.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : listQuery.isError ? (
                        <EmptyState
                            title="Riwayat sisa bahan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => listQuery.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Bahan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Mesin</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Desainer</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Dimensi</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(listQuery.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={6}>Belum ada sisa bahan tercatat.</TableEmpty>
                                    ) : (
                                        (listQuery.data?.data ?? []).map((waste) => (
                                            <TableRow key={waste.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatDate(waste.logged_at)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium">
                                                        {waste.material?.name ?? '-'}
                                                    </span>
                                                    {waste.notes ? (
                                                        <p className="text-light-muted text-xs dark:text-gray-400">
                                                            {waste.notes}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.machine?.name ?? '-'}</TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.designer?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {waste.quantity}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.dimensions ?? '-'}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Pagination
                                    page={listQuery.data?.meta.current_page ?? 1}
                                    lastPage={listQuery.data?.meta.last_page ?? 1}
                                    total={listQuery.data?.meta.total}
                                    perPage={listQuery.data?.meta.per_page}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>
            {notice ? <Toast message={notice} onClose={() => setNotice(null)} /> : null}
        </div>
    );
}
