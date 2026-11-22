import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, type ReactNode } from 'react';

import type { Paginated } from '../../api/types';
import { PageHeader } from '../PageHeader';
import { Button } from '../ui/Button';
import { Card, CardBody } from '../ui/Card';
import { Checkbox } from '../ui/Checkbox';
import { Input } from '../ui/Input';
import { Modal } from '../ui/Modal';
import { Pagination } from '../ui/Pagination';
import { Select } from '../ui/Select';
import { Spinner } from '../ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../ui/Table';
import { Toast } from '../ui/Toast';
import type { ResourceColumn } from '../ResourceTable';
import { errorMessage, mapApiErrors } from '../../lib/errors';

export interface CrudField<T> {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'email' | 'password' | 'select' | 'checkbox';
    options?: Array<{ value: string; label: string }>;
    required?: boolean;
    defaultValue?: string | number | boolean;
    getValue?: (row: T) => string | number | boolean | null | undefined;
    hideOnEdit?: boolean;
}

type FormValues = Record<string, string | number | boolean>;

export function CrudPage<T extends { id: string }>({
    eyebrow,
    title,
    description,
    queryKey,
    queryFn,
    columns,
    fields,
    create,
    update,
    remove,
    rowActions,
    createLabel = 'Tambah data',
}: {
    eyebrow: string;
    title: string;
    description?: string;
    queryKey: readonly unknown[];
    queryFn: (page: number) => Promise<Paginated<T>>;
    columns: Array<ResourceColumn<T>>;
    fields: Array<CrudField<T>>;
    create?: (values: FormValues) => Promise<unknown>;
    update?: (id: string, values: FormValues) => Promise<unknown>;
    remove?: (id: string) => Promise<unknown>;
    rowActions?: (row: T) => ReactNode;
    createLabel?: string;
}) {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<T | null>(null);
    const [values, setValues] = useState<FormValues>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const query = useQuery({
        queryKey: [...queryKey, page],
        queryFn: () => queryFn(page),
    });

    const valuesFor = (row: T | null): FormValues => {
        const next: FormValues = {};

        for (const field of fields) {
            const current = row
                ? (field.getValue?.(row) ?? (row as Record<string, unknown>)[field.key])
                : field.defaultValue;

            next[field.key] = (current ?? '') as string | number | boolean;
        }

        return next;
    };

    const invalidate = () => {
        void queryClient.invalidateQueries({ queryKey });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const save = useMutation({
        mutationFn: async () => {
            if (editing && update) {
                return update(editing.id, values);
            }

            if (!create) {
                return null;
            }

            return create(values);
        },
        onSuccess: () => {
            invalidate();
            setOpen(false);
            setEditing(null);
            setNotice(editing ? 'Data diperbarui.' : 'Data ditambahkan.');
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const destroy = useMutation({
        mutationFn: (id: string) => {
            if (!remove) {
                return Promise.resolve();
            }

            return remove(id);
        },
        onSuccess: () => {
            invalidate();
            setNotice('Data dihapus.');
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    const openCreate = () => {
        setEditing(null);
        setValues(valuesFor(null));
        setErrors({});
        setOpen(true);
    };

    const openEdit = (row: T) => {
        setEditing(row);
        setValues(valuesFor(row));
        setErrors({});
        setOpen(true);
    };

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow={eyebrow}
                title={title}
                description={description}
                actions={
                    create ? (
                        <Button size="sm" onClick={openCreate}>
                            {createLabel}
                        </Button>
                    ) : null
                }
            />

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
                                        {columns.map((column) => (
                                            <TableHeaderCell key={column.header}>
                                                {column.header}
                                            </TableHeaderCell>
                                        ))}
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(query.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={columns.length + 1}>
                                            Belum ada data.
                                        </TableEmpty>
                                    ) : (
                                        (query.data?.data ?? []).map((row) => (
                                            <TableRow key={row.id}>
                                                {columns.map((column) => (
                                                    <TableCell key={column.header}>
                                                        {column.cell(row)}
                                                    </TableCell>
                                                ))}
                                                <TableCell className="border-light-border sticky right-0 border-l bg-light-surface dark:border-surface-300 dark:bg-surface-700">
                                                    <div className="flex flex-wrap gap-2">
                                                        {rowActions?.(row)}
                                                        {update ? (
                                                            <Button
                                                                size="sm"
                                                                variant="secondary"
                                                                onClick={() => openEdit(row)}
                                                            >
                                                                Ubah
                                                            </Button>
                                                        ) : null}
                                                        {remove ? (
                                                            <Button
                                                                size="sm"
                                                                variant="danger"
                                                                onClick={() => {
                                                                    if (
                                                                        window.confirm(
                                                                            'Hapus data ini?'
                                                                        )
                                                                    ) {
                                                                        destroy.mutate(row.id);
                                                                    }
                                                                }}
                                                            >
                                                                Hapus
                                                            </Button>
                                                        ) : null}
                                                    </div>
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
                                    total={query.data?.meta.total}
                                    perPage={query.data?.meta.per_page}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>

            <Modal
                open={open}
                onClose={() => {
                    setOpen(false);
                    setEditing(null);
                }}
                title={editing ? 'Ubah data' : createLabel}
            >
                <div className="space-y-4">
                    {fields.map((field) => {
                        if (editing && field.hideOnEdit) {
                            return null;
                        }

                        const value = values[field.key] ?? '';
                        const error = errors[field.key];

                        if (field.type === 'checkbox') {
                            return (
                                <Checkbox
                                    key={field.key}
                                    label={field.label}
                                    checked={Boolean(value)}
                                    error={error}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            [field.key]: event.target.checked,
                                        })
                                    }
                                />
                            );
                        }

                        if (field.type === 'select') {
                            return (
                                <Select
                                    key={field.key}
                                    label={field.label}
                                    value={String(value)}
                                    error={error}
                                    onChange={(event) =>
                                        setValues({ ...values, [field.key]: event.target.value })
                                    }
                                >
                                    <option value="">Pilih</option>
                                    {(field.options ?? []).map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            );
                        }

                        return (
                            <Input
                                key={field.key}
                                label={field.label}
                                type={field.type ?? 'text'}
                                value={String(value)}
                                error={error}
                                onChange={(event) =>
                                    setValues({ ...values, [field.key]: event.target.value })
                                }
                            />
                        );
                    })}

                    <div className="flex justify-end gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setOpen(false);
                                setEditing(null);
                            }}
                        >
                            Batal
                        </Button>
                        <Button loading={save.isPending} onClick={() => save.mutate()}>
                            Simpan
                        </Button>
                    </div>
                </div>
            </Modal>

            {notice ? <Toast message={notice} onClose={() => setNotice(null)} /> : null}
        </div>
    );
}
