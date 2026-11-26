import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate } from 'react-router';

import { api } from '../../api/queries';
import type { Quote } from '../../api/queries';
import type { Customer } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast, type ToastTone } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

/**
 * Representasi satu baris item pesanan yang sudah dihitung penawarannya di keranjang.
 */
interface CartLine {
    quote: Quote;
    product_name: string;
    material_name: string;
    attribute_labels: string[];
    selected_attributes: string[];
    file_name: string;
    notes: string;
    width: number;
    length: number;
    quantity: number;
}

/**
 * State formulir draf item sebelum ditambahkan ke keranjang.
 */
interface DraftItemState {
    product_id: string;
    material_id: string;
    finishing_id: string;
    display_id: string;
    quantity: number;
    width: number;
    length: number;
    file_name: string;
    notes: string;
}

/**
 * State notifikasi toast beserta tingkat nadanya.
 */
interface NoticeState {
    message: string;
    tone: ToastTone;
}

const emptyItem: DraftItemState = {
    product_id: '',
    material_id: '',
    finishing_id: '',
    display_id: '',
    quantity: 1,
    width: 1,
    length: 1,
    file_name: '',
    notes: '',
};

/**
 * Halaman pembuatan pesanan baru untuk peran desainer (FR8 / PB-02).
 *
 * Menyediakan pencarian pelanggan sebagai alur bawaan beserta pembuatan pelanggan baru,
 * alur pemilihan Produk -> Bahan -> Finishing -> Display, pratinjau penawaran server-side,
 * dan pengiriman invoice dalam satu transaksi.
 */
export function DesignerNewOrderPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const optionsQuery = useQuery({ queryKey: ['quote-options'], queryFn: () => api.quoteOptions() });

    const [customer, setCustomer] = useState<Customer | null>(null);
    const [customerSearch, setCustomerSearch] = useState('');

    const [item, setItem] = useState<DraftItemState>(emptyItem);
    const [itemError, setItemError] = useState<string | null>(null);
    const [quote, setQuote] = useState<Quote | null>(null);
    const [cart, setCart] = useState<CartLine[]>([]);
    const [deadline, setDeadline] = useState('');
    const [source, setSource] = useState<'Offline' | 'Online'>('Offline');
    const [notice, setNotice] = useState<NoticeState | null>(null);
    const [submitErrors, setSubmitErrors] = useState<Record<string, string>>({});

    const customersQuery = useQuery({
        queryKey: ['customers', 'search', customerSearch.trim()],
        queryFn: () => api.customers({ search: customerSearch.trim() }),
        enabled: !customer,
    });

    const options = optionsQuery.data;
    const selectedProduct = (options?.products ?? []).find((product) => product.id === item.product_id);
    const isAreaProduct = selectedProduct?.pricing_type === 'area';
    const needsWidth = isAreaProduct;
    const needsLength = isAreaProduct;
    const mappedMaterialIds = selectedProduct?.material_ids ?? [];
    const availableMaterials = (options?.materials ?? []).filter(
        (material) => mappedMaterialIds.length === 0 || mappedMaterialIds.includes(material.id)
    );

    const finishingOptions = (options?.attributes ?? []).filter(
        (attribute) => attribute.type === 'finishing'
    );
    const displayOptions = (options?.attributes ?? []).filter(
        (attribute) => attribute.type === 'display'
    );

    const customerList = customersQuery.data?.data ?? [];

    /**
     * Memilih pelanggan dari hasil pencarian atau pembuatan baru serta menyetel ulang penawaran stale.
     */
    const handleSelectCustomer = (selected: Customer) => {
        if (cart.length > 0 && selected.type !== customer?.type) {
            setNotice({
                message: 'Pelanggan dipilih. Harga akhir invoice akan dihitung ulang di server sesuai tipe pelanggan.',
                tone: 'info',
            });
        }
        setCustomer(selected);
        setQuote(null);
        setSubmitErrors({});
    };

    /**
     * Memperbarui state draf item dan menghapus penawaran lama saat parameter harga berubah.
     */
    const updatePricingItem = (patch: Partial<DraftItemState>) => {
        setItem((current) => ({ ...current, ...patch }));
        setQuote(null);
        setItemError(null);
    };

    const calculate = useMutation({
        mutationFn: () => {
            if (!item.product_id || !item.material_id) {
                throw new Error('Pilih produk dan bahan terlebih dahulu.');
            }

            if (!Number.isFinite(item.quantity) || item.quantity <= 0) {
                throw new Error('Jumlah pesanan harus lebih besar dari nol.');
            }

            if (needsWidth && (!Number.isFinite(item.width) || item.width <= 0)) {
                throw new Error('Lebar harus lebih besar dari nol untuk produk berbasis luas.');
            }

            if (needsLength && (!Number.isFinite(item.length) || item.length <= 0)) {
                throw new Error('Panjang harus lebih besar dari nol untuk produk ini.');
            }

            const selectedAttributes = [item.finishing_id, item.display_id].filter(Boolean);

            return api.quote({
                product_id: item.product_id,
                material_id: item.material_id,
                selected_attributes: selectedAttributes,
                quantity: item.quantity,
                width: needsWidth ? item.width : undefined,
                length: needsLength ? item.length : undefined,
                customer_type: customer?.type ?? 'Reguler',
            });
        },
        onSuccess: (response) => {
            setItemError(null);
            setQuote(response.quote);
        },
        onError: (error) => {
            const message = errorMessage(error);
            setQuote(null);
            setItemError(message);
            setNotice({ message, tone: 'error' });
        },
    });

    const submit = useMutation({
        mutationFn: () => {
            if (!customer) {
                throw new Error('Pilih atau buat pelanggan terlebih dahulu.');
            }

            return api.createInvoice({
                customer_id: customer.id,
                discount: 0,
                deadline: deadline || null,
                source,
                items: cart.map((line) => ({
                    pricing_id: line.quote.pricing_id,
                    file_name: line.file_name.trim() || null,
                    notes: line.notes.trim() || null,
                    quantity: line.quantity,
                    width: line.width > 0 ? line.width : undefined,
                    length: line.length > 0 ? line.length : undefined,
                    selected_attributes: line.selected_attributes,
                    is_manual_price: false,
                })),
            });
        },
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['designer', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['cashier', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice({
                message: `Invoice ${response.invoice.invoice_number} dibuat.`,
                tone: 'success',
            });
            setCart([]);
            setQuote(null);
            setItem(emptyItem);
            setDeadline('');
            setSource('Offline');
            setSubmitErrors({});
            navigate('/designer/orders');
        },
        onError: (error) => {
            setSubmitErrors(mapApiErrors(error));
            setNotice({ message: errorMessage(error), tone: 'error' });
        },
    });

    /**
     * Menambahkan hasil kalkulasi penawaran saat ini ke dalam daftar keranjang pesanan.
     */
    const addLine = () => {
        if (!quote) {
            setNotice({ message: 'Hitung penawaran sebelum menambah item.', tone: 'error' });
            return;
        }

        const productName = selectedProduct?.name ?? 'Produk';
        const materialName =
            (options?.materials ?? []).find((material) => material.id === item.material_id)?.name ?? 'Bahan';
        const selectedAttributes = [item.finishing_id, item.display_id].filter(Boolean);
        const attributeLabels = selectedAttributes
            .map((id) => (options?.attributes ?? []).find((attribute) => attribute.id === id)?.name)
            .filter((name): name is string => Boolean(name));

        setCart((current) => [
            ...current,
            {
                quote,
                product_name: productName,
                material_name: materialName,
                attribute_labels: attributeLabels,
                selected_attributes: selectedAttributes,
                file_name: item.file_name,
                notes: item.notes,
                width: needsWidth ? item.width : 0,
                length: needsLength ? item.length : 0,
                quantity: item.quantity,
            },
        ]);
        setQuote(null);
        setItemError(null);
        setSubmitErrors({});
        setItem({ ...emptyItem, file_name: '', notes: '' });
    };

    /**
     * Menghapus baris item tertentu dari keranjang berdasarkan indeks.
     */
    const removeLine = (index: number) => {
        setCart((current) => current.filter((_, position) => position !== index));
    };

    const cartTotal = cart.reduce((total, line) => total + Number(line.quote.subtotal), 0);

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Desainer"
                title="Pesanan Baru"
                description="Cari atau buat pelanggan, atur item, pratinjau harga, lalu kirim invoice."
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
                <div className="min-w-0 space-y-6">
                    <Card className="min-w-0">
                        <CardHeader>
                            <h2 className="font-semibold">Pelanggan</h2>
                            {!customer ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => navigate('/designer/customers/new')}
                                >
                                    Tambah Pelanggan
                                </Button>
                            ) : null}
                        </CardHeader>
                        <CardBody className="space-y-4">
                            {customer ? (
                                <div className="border-light-border bg-light-surface-2 dark:border-surface-300 dark:bg-surface-600 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3">
                                    <div className="min-w-0 space-y-0.5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">{customer.name}</p>
                                            <Badge tone={customer.type === 'Reseller' ? 'brand' : 'neutral'}>
                                                {customer.type ?? 'Reguler'}
                                            </Badge>
                                        </div>
                                        <p className="text-light-muted-strong text-sm dark:text-gray-400">
                                            {customer.phone ?? 'Tanpa telepon'}
                                            {customer.address ? ` · ${customer.address}` : ''}
                                        </p>
                                    </div>
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => {
                                            setCustomer(null);
                                            setQuote(null);
                                        }}
                                    >
                                        Ubah
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <Input
                                        label="Cari pelanggan"
                                        placeholder="Ketik nama atau nomor telepon..."
                                        value={customerSearch}
                                        onChange={(event) => setCustomerSearch(event.target.value)}
                                    />
                                    {customersQuery.isLoading ? (
                                        <div role="status" className="flex justify-center py-4">
                                            <Spinner className="text-brand-primary h-5 w-5" />
                                        </div>
                                    ) : customersQuery.isError ? (
                                        <EmptyState
                                            title="Daftar pelanggan gagal dimuat"
                                            description="Periksa koneksi lalu coba lagi."
                                            action={
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => customersQuery.refetch()}
                                                >
                                                    Coba lagi
                                                </Button>
                                            }
                                        />
                                    ) : customerList.length === 0 ? (
                                        <div className="border-light-border dark:border-surface-300 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed px-4 py-3 text-sm">
                                            <span className="text-light-muted-strong dark:text-gray-400">
                                                Pelanggan tidak ditemukan.
                                            </span>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => navigate('/designer/customers/new')}
                                            >
                                                Buat pelanggan baru
                                            </Button>
                                        </div>
                                    ) : (
                                        <ul className="divide-light-border border-light-border dark:divide-surface-300 dark:border-surface-300 divide-y rounded-xl border">
                                            {customerList.map((entry) => (
                                                <li
                                                    key={entry.id}
                                                    className="flex flex-wrap items-center justify-between gap-3 px-3.5 py-2.5"
                                                >
                                                    <div className="min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span className="font-medium">{entry.name}</span>
                                                            <Badge
                                                                tone={
                                                                    entry.type === 'Reseller' ? 'brand' : 'neutral'
                                                                }
                                                            >
                                                                {entry.type ?? 'Reguler'}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-light-muted-strong truncate text-xs dark:text-gray-400">
                                                            {entry.phone ?? 'Tanpa telepon'}
                                                            {entry.address ? ` · ${entry.address}` : ''}
                                                        </p>
                                                    </div>
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() => handleSelectCustomer(entry)}
                                                    >
                                                        Pilih
                                                    </Button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            )}
                        </CardBody>
                    </Card>

                    <Card className="min-w-0">
                        <CardHeader>
                            <h2 className="font-semibold">Item</h2>
                        </CardHeader>
                        <CardBody className="space-y-4">
                            {optionsQuery.isLoading ? (
                                <div role="status" className="flex justify-center py-10">
                                    <Spinner className="text-brand-primary h-6 w-6" />
                                </div>
                            ) : optionsQuery.isError ? (
                                <EmptyState
                                    title="Pilihan produk gagal dimuat"
                                    description="Periksa koneksi lalu coba lagi."
                                    action={
                                        <Button variant="secondary" onClick={() => optionsQuery.refetch()}>
                                            Coba lagi
                                        </Button>
                                    }
                                />
                            ) : (
                                <>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <Select
                                            label="Produk"
                                            value={item.product_id}
                                            onChange={(event) => {
                                                const nextProductId = event.target.value;
                                                const nextProduct = (options?.products ?? []).find(
                                                    (product) => product.id === nextProductId
                                                );
                                                const nextAllowedIds =
                                                    nextProduct?.material_ids ?? [];
                                                const keepMaterial =
                                                    nextAllowedIds.length === 0 ||
                                                    nextAllowedIds.includes(item.material_id);

                                                updatePricingItem({
                                                    product_id: nextProductId,
                                                    material_id: keepMaterial
                                                        ? item.material_id
                                                        : '',
                                                    finishing_id: '',
                                                    display_id: '',
                                                });
                                            }}
                                        >
                                            <option value="">Pilih produk</option>
                                            {(options?.products ?? []).map((product) => (
                                                <option key={product.id} value={product.id}>
                                                    {product.name}
                                                </option>
                                            ))}
                                        </Select>
                                        <Select
                                            label="Bahan"
                                            value={item.material_id}
                                            onChange={(event) =>
                                                updatePricingItem({ material_id: event.target.value })
                                            }
                                        >
                                            <option value="">Pilih bahan</option>
                                            {availableMaterials.map((material) => (
                                                <option key={material.id} value={material.id}>
                                                    {material.name}
                                                </option>
                                            ))}
                                        </Select>
                                        {finishingOptions.length > 0 ? (
                                            <Select
                                                label="Finishing"
                                                value={item.finishing_id}
                                                onChange={(event) =>
                                                    updatePricingItem({ finishing_id: event.target.value })
                                                }
                                            >
                                                <option value="">Tanpa finishing</option>
                                                {finishingOptions.map((attribute) => (
                                                    <option key={attribute.id} value={attribute.id}>
                                                        {attribute.name}
                                                    </option>
                                                ))}
                                            </Select>
                                        ) : null}
                                        {displayOptions.length > 0 ? (
                                            <Select
                                                label="Display"
                                                value={item.display_id}
                                                onChange={(event) =>
                                                    updatePricingItem({ display_id: event.target.value })
                                                }
                                            >
                                                <option value="">Tanpa display</option>
                                                {displayOptions.map((attribute) => (
                                                    <option key={attribute.id} value={attribute.id}>
                                                        {attribute.name}
                                                    </option>
                                                ))}
                                            </Select>
                                        ) : null}
                                        <Input
                                            label="Jml"
                                            type="number"
                                            min="1"
                                            value={item.quantity}
                                            onChange={(event) =>
                                                updatePricingItem({ quantity: Number(event.target.value) })
                                            }
                                        />
                                        {needsWidth ? (
                                            <Input
                                                label="Lebar (m)"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={item.width}
                                                onChange={(event) =>
                                                    updatePricingItem({ width: Number(event.target.value) })
                                                }
                                            />
                                        ) : null}
                                        {needsLength ? (
                                            <Input
                                                label="Panjang (m)"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={item.length}
                                                onChange={(event) =>
                                                    updatePricingItem({ length: Number(event.target.value) })
                                                }
                                            />
                                        ) : null}
                                        <Input
                                            label="Nama file"
                                            value={item.file_name}
                                            onChange={(event) =>
                                                setItem({ ...item, file_name: event.target.value })
                                            }
                                        />
                                        <Input
                                            label="Catatan"
                                            value={item.notes}
                                            onChange={(event) =>
                                                setItem({ ...item, notes: event.target.value })
                                            }
                                        />
                                    </div>

                                    {itemError ? (
                                        <p role="alert" className="text-sm text-red-600 dark:text-red-400">
                                            {itemError}
                                        </p>
                                    ) : null}

                                    <div className="border-light-border bg-light-surface-2/60 dark:border-surface-300 dark:bg-surface-600/40 rounded-xl border px-4 py-3">
                                        <div className="flex flex-wrap items-center justify-between gap-4">
                                            <dl className="flex flex-wrap items-baseline gap-x-6 gap-y-2">
                                                <div>
                                                    <dt className="text-light-muted text-[10px] font-semibold tracking-wider uppercase dark:text-gray-400">
                                                        Subtotal
                                                    </dt>
                                                    <dd className="text-light-text text-lg font-extrabold tabular-nums dark:text-gray-100">
                                                        {quote ? formatMoney(quote.subtotal) : '-'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-light-muted text-[10px] font-semibold tracking-wider uppercase dark:text-gray-400">
                                                        Area
                                                    </dt>
                                                    <dd className="text-light-muted-strong text-sm font-semibold tabular-nums dark:text-gray-300">
                                                        {quote ? `${quote.area} m²` : '-'}
                                                    </dd>
                                                </div>
                                            </dl>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Button
                                                    variant="secondary"
                                                    loading={calculate.isPending}
                                                    onClick={() => calculate.mutate()}
                                                >
                                                    Hitung penawaran
                                                </Button>
                                                <Button onClick={addLine} disabled={!quote}>
                                                    Tambah ke keranjang
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </>
                            )}
                        </CardBody>
                    </Card>
                </div>

                <Card className="min-w-0 self-start xl:sticky xl:top-6">
                    <CardHeader>
                        <h2 className="font-semibold">Keranjang</h2>
                    </CardHeader>
                    <CardBody className="space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Input
                                label="Tenggat selesai"
                                type="date"
                                value={deadline}
                                error={submitErrors.deadline}
                                onChange={(event) => setDeadline(event.target.value)}
                            />
                            <Select
                                label="Sumber pesanan"
                                value={source}
                                error={submitErrors.source}
                                onChange={(event) =>
                                    setSource(event.target.value === 'Online' ? 'Online' : 'Offline')
                                }
                            >
                                <option value="Offline">Offline</option>
                                <option value="Online">Online</option>
                            </Select>
                        </div>

                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Item</TableHeaderCell>
                                    <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                    <TableHeaderCell className="text-right">Subtotal</TableHeaderCell>
                                    <TableHeaderCell>Aksi</TableHeaderCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {cart.length === 0 ? (
                                    <TableEmpty colSpan={4}>Tambahkan minimal satu item.</TableEmpty>
                                ) : (
                                    cart.map((line, index) => (
                                        <TableRow key={`${line.quote.pricing_id}-${index}`}>
                                            <TableCell>
                                                <p className="font-medium">
                                                    {line.file_name || line.product_name}
                                                </p>
                                                <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                    {line.product_name} · {line.material_name}
                                                    {line.attribute_labels.length > 0
                                                        ? ` · ${line.attribute_labels.join(', ')}`
                                                        : ''}
                                                </p>
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                {line.quantity}
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                {formatMoney(line.quote.subtotal)}
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeLine(index)}
                                                >
                                                    Hapus
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                            <tfoot className="border-light-border dark:border-surface-300 border-t">
                                <TableRow>
                                    <td
                                        colSpan={2}
                                        className="text-light-text px-3 py-2 text-right align-middle font-semibold dark:text-gray-100"
                                    >
                                        Total
                                    </td>
                                    <td
                                        colSpan={2}
                                        className="text-brand-fill dark:text-brand-primary-light px-3 py-2 text-right align-middle text-lg font-bold whitespace-nowrap tabular-nums"
                                    >
                                        {formatMoney(cartTotal)}
                                    </td>
                                </TableRow>
                            </tfoot>
                        </Table>

                        {submitErrors.items ? (
                            <p className="text-sm text-red-600 dark:text-red-400">{submitErrors.items}</p>
                        ) : null}

                        <Button
                            className="w-full"
                            loading={submit.isPending}
                            disabled={!customer || cart.length === 0}
                            onClick={() => submit.mutate()}
                        >
                            Buat invoice
                        </Button>
                    </CardBody>
                </Card>
            </div>

            {notice ? (
                <Toast message={notice.message} tone={notice.tone} onClose={() => setNotice(null)} />
            ) : null}
        </div>
    );
}
