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
            setNotice('Waste logged and stock deducted.');
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const options = optionsQuery.data;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Production"
                title="Waste Logging"
                description="Log failed prints and deduct material stock."
            />

            <Card>
                <CardBody className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-5">
                        <Select
                            label="Material"
                            value={form.material_id}
                            error={errors.material_id}
                            onChange={(event) => setForm({ ...form, material_id: event.target.value })}
                        >
                            <option value="">Select material</option>
                            {(options?.materials ?? []).map((material) => (
                                <option key={material.id} value={material.id}>
                                    {material.name}
                                </option>
                            ))}
                        </Select>
                        <Select
                            label="Machine"
                            value={form.machine_id}
                            error={errors.machine_id}
                            onChange={(event) => setForm({ ...form, machine_id: event.target.value })}
                        >
                            <option value="">No machine</option>
                            {(options?.machines ?? []).map((machine) => (
                                <option key={machine.id} value={machine.id}>
                                    {machine.name}
                                </option>
                            ))}
                        </Select>
                        <Select
                            label="Designer"
                            value={form.designer_id}
                            error={errors.designer_id}
                            onChange={(event) => setForm({ ...form, designer_id: event.target.value })}
                        >
                            <option value="">No designer</option>
                            {(options?.designers ?? []).map((designer) => (
                                <option key={designer.id} value={designer.id}>
                                    {designer.name}
                                </option>
                            ))}
                        </Select>
                        <Input
                            label="Quantity"
                            type="number"
                            min="0"
                            value={form.quantity}
                            error={errors.quantity}
                            onChange={(event) => setForm({ ...form, quantity: event.target.value })}
                        />
                        <Input
                            label="Dimensions"
                            value={form.dimensions}
                            onChange={(event) => setForm({ ...form, dimensions: event.target.value })}
                        />
                    </div>
                    <Input
                        label="Notes"
                        value={form.notes}
                        error={errors.notes}
                        onChange={(event) => setForm({ ...form, notes: event.target.value })}
                    />
                    <Button loading={create.isPending} onClick={() => create.mutate()}>
                        Log waste
                    </Button>
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    {listQuery.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Date</TableHeaderCell>
                                        <TableHeaderCell>Material</TableHeaderCell>
                                        <TableHeaderCell>Machine</TableHeaderCell>
                                        <TableHeaderCell>Designer</TableHeaderCell>
                                        <TableHeaderCell>Quantity</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(listQuery.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={5}>No waste logged.</TableEmpty>
                                    ) : (
                                        (listQuery.data?.data ?? []).map((waste) => (
                                            <TableRow key={waste.id}>
                                                <TableCell>{formatDate(waste.logged_at)}</TableCell>
                                                <TableCell>{waste.material?.name ?? '-'}</TableCell>
                                                <TableCell>{waste.machine?.name ?? '-'}</TableCell>
                                                <TableCell>{waste.designer?.name ?? '-'}</TableCell>
                                                <TableCell>{waste.quantity}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Pagination
                                    page={listQuery.data?.meta.current_page ?? 1}
                                    lastPage={listQuery.data?.meta.last_page ?? 1}
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
