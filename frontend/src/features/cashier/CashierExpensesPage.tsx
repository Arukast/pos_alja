import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatDate, todayIso } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function CashierExpensesPage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [form, setForm] = useState({
        amount: '',
        type: 'laba',
        category: 'Operasional',
        description: '',
        incurred_at: todayIso(),
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const query = useQuery({
        queryKey: ['expenses', page],
        queryFn: () => api.expenses({ page }),
    });

    const create = useMutation({
        mutationFn: () =>
            api.createExpense({
                ...form,
                amount: Number(form.amount),
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['expenses'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setForm({ ...form, amount: '', description: '' });
            setErrors({});
            setNotice('Pengeluaran dicatat.');
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Kasir"
                title="Pengeluaran"
                description="Catat pengeluaran cabang dan tinjau daftarnya."
            />

            <Card>
                <CardBody className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-4">
                        <Input
                            label="Jumlah"
                            type="number"
                            min="0"
                            value={form.amount}
                            error={errors.amount}
                            onChange={(event) => setForm({ ...form, amount: event.target.value })}
                        />
                        <Select
                            label="Tipe"
                            value={form.type}
                            onChange={(event) => setForm({ ...form, type: event.target.value })}
                        >
                            <option value="laba">Laba</option>
                            <option value="modal">Modal</option>
                        </Select>
                        <Input
                            label="Kategori"
                            value={form.category}
                            error={errors.category}
                            onChange={(event) => setForm({ ...form, category: event.target.value })}
                        />
                        <Input
                            label="Tanggal"
                            type="date"
                            value={form.incurred_at}
                            error={errors.incurred_at}
                            onChange={(event) => setForm({ ...form, incurred_at: event.target.value })}
                        />
                    </div>
                    <Input
                        label="Deskripsi"
                        value={form.description}
                        error={errors.description}
                        onChange={(event) => setForm({ ...form, description: event.target.value })}
                    />
                    <Button loading={create.isPending} onClick={() => create.mutate()}>
                        Catat Pengeluaran
                    </Button>
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Tipe</TableHeaderCell>
                                        <TableHeaderCell>Kategori</TableHeaderCell>
                                        <TableHeaderCell>Deskripsi</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(query.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada pengeluaran.</TableEmpty>
                                    ) : (
                                        (query.data?.data ?? []).map((expense) => (
                                            <TableRow key={expense.id}>
                                                <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                <TableCell>{expense.type}</TableCell>
                                                <TableCell>{expense.category}</TableCell>
                                                <TableCell>{expense.description ?? '-'}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(expense.amount)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Pagination
                                    page={query.data?.meta.current_page ?? 1}
                                    lastPage={query.data?.meta.last_page ?? 1}
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
