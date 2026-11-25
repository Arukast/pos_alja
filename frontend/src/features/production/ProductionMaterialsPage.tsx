import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ProductionMaterial } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { EmptyState } from '../../components/ui/EmptyState';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatQuantity } from '../../lib/money';

export function ProductionMaterialsPage() {
    const [page, setPage] = useState(1);
    const [lowStock, setLowStock] = useState(false);

    const query = useQuery({
        queryKey: ['production', 'materials', lowStock, page],
        queryFn: () =>
            api.materials<ProductionMaterial>({
                page,
                low_stock: lowStock ? 1 : undefined,
            }),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Bahan & Stok"
                description="Stok bahan cabang dan peringatan stok minimum."
            />

            <Card>
                <CardBody className="space-y-4">
                    <Checkbox
                        label="Hanya stok minimum"
                        checked={lowStock}
                        onChange={(event) => {
                            setLowStock(event.target.checked);
                            setPage(1);
                        }}
                    />

                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Bahan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table ariaLabel="Bahan & Stok">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Bahan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">
                                            Kategori
                                        </TableHeaderCell>
                                        <TableHeaderCell className="text-right whitespace-nowrap">
                                            Stok
                                        </TableHeaderCell>
                                        <TableHeaderCell className="text-right whitespace-nowrap">
                                            Minimum
                                        </TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(query.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={5}>
                                            {lowStock
                                                ? 'Tidak ada bahan pada batas stok minimum.'
                                                : 'Belum ada bahan.'}
                                        </TableEmpty>
                                    ) : (
                                        (query.data?.data ?? []).map((material) => (
                                            <TableRow
                                                key={material.id}
                                                className={
                                                    material.is_low_stock === true
                                                        ? 'bg-amber-50/60 dark:bg-amber-500/10'
                                                        : undefined
                                                }
                                            >
                                                <TableCell>
                                                    <span className="font-medium">{material.name}</span>
                                                    {material.type || material.dimensions ? (
                                                        <p className="text-light-muted text-xs dark:text-gray-400">
                                                            {[material.type, material.dimensions]
                                                                .filter(Boolean)
                                                                .join(' · ')}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">
                                                    {material.category?.name ?? '-'}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatQuantity(material.current_stock)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatQuantity(material.min_stock)}
                                                </TableCell>
                                                <TableCell>
                                                    {material.is_low_stock === true ? (
                                                        <Badge tone="warning">Menipis</Badge>
                                                    ) : (
                                                        <Badge tone="success">Tersedia</Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={query.data?.meta.current_page ?? 1}
                                lastPage={query.data?.meta.last_page ?? 1}
                                total={query.data?.meta.total}
                                perPage={query.data?.meta.per_page}
                                onPageChange={setPage}
                            />
                        </>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
