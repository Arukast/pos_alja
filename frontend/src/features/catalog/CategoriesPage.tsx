import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Category } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Spinner } from '../../components/ui/Spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';

export function CategoriesPage({
    eyebrow,
    queryKeyPrefix,
    readOnly = false,
}: {
    eyebrow: string;
    queryKeyPrefix: 'owner' | 'manager';
    readOnly?: boolean;
}) {
    const queryClient = useQueryClient();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<{
        message: string;
        tone: 'success' | 'error';
    } | null>(null);

    const queryKey = [queryKeyPrefix, 'categories'] as const;
    const query = useQuery({
        queryKey,
        queryFn: api.categories,
    });

    const invalidate = () => {
        void queryClient.invalidateQueries({ queryKey });
        void queryClient.invalidateQueries({ queryKey: ['categories'] });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const save = useMutation({
        mutationFn: () => {
            const payload = {
                name,
                description: description.trim() === '' ? null : description.trim(),
                is_active: isActive,
            };

            if (editing) {
                return api.admin.updateCategory(editing.id, payload);
            }

            return api.admin.createCategory(payload);
        },
        onSuccess: () => {
            invalidate();
            setOpen(false);
            setEditing(null);
            setNotice({
                message: editing ? 'Kategori diperbarui.' : 'Kategori ditambahkan.',
                tone: 'success',
            });
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice({ message: errorMessage(error), tone: 'error' });
        },
    });

    const openCreate = () => {
        setEditing(null);
        setName('');
        setDescription('');
        setIsActive(true);
        setErrors({});
        setOpen(true);
    };

    const openEdit = (category: Category) => {
        setEditing(category);
        setName(category.name);
        setDescription(category.description ?? '');
        setIsActive(category.is_active);
        setErrors({});
        setOpen(true);
    };

    const rows = query.data?.data ?? [];

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow={eyebrow}
                title="Kategori Produk"
                description="Kelola kategori produk, deskripsi, dan status aktif."
                actions={
                    readOnly ? null : (
                        <Button size="sm" onClick={openCreate}>
                            Tambah kategori
                        </Button>
                    )
                }
            />

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Data gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <Table ariaLabel="Kategori Produk">
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Nama</TableHeaderCell>
                                    <TableHeaderCell className="hidden sm:table-cell">
                                        Deskripsi
                                    </TableHeaderCell>
                                    <TableHeaderCell className="hidden text-right tabular-nums sm:table-cell">
                                        Produk
                                    </TableHeaderCell>
                                    <TableHeaderCell>Status</TableHeaderCell>
                                    {readOnly ? null : (
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    )}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableEmpty colSpan={readOnly ? 4 : 5}>
                                        Belum ada data.
                                    </TableEmpty>
                                ) : (
                                    rows.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell>
                                                <span className="font-medium">{category.name}</span>
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell">
                                                {category.description ?? '-'}
                                            </TableCell>
                                            <TableCell className="hidden text-right tabular-nums sm:table-cell">
                                                {category.products_count ?? 0}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    tone={
                                                        category.is_active ? 'success' : 'neutral'
                                                    }
                                                >
                                                    {category.is_active ? 'Aktif' : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            {readOnly ? null : (
                                                <TableCell className="border-light-border bg-light-surface sticky right-0 border-l shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] dark:border-surface-300 dark:bg-surface-700">
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        aria-label={`Ubah ${category.name}`}
                                                        onClick={() => openEdit(category)}
                                                    >
                                                        Ubah
                                                    </Button>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    )}
                </CardBody>
            </Card>

            {readOnly ? null : (
                <Modal
                    open={open}
                    onClose={() => {
                        setOpen(false);
                        setEditing(null);
                    }}
                    title={editing ? 'Ubah kategori' : 'Tambah kategori'}
                >
                    <div className="space-y-4">
                        <Input
                            label="Nama"
                            required
                            value={name}
                            error={errors.name}
                            onChange={(event) => setName(event.target.value)}
                        />

                        <Input
                            label="Deskripsi"
                            maxLength={255}
                            value={description}
                            error={errors.description}
                            onChange={(event) => setDescription(event.target.value)}
                        />

                        <Checkbox
                            label="Aktif"
                            checked={isActive}
                            error={errors.is_active}
                            onChange={(event) => setIsActive(event.target.checked)}
                        />

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
            )}

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
