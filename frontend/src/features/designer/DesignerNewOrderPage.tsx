import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate } from 'react-router';

import { api } from '../../api/queries';
import type { Quote } from '../../api/queries';
import type { Customer } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

interface CartLine {
    quote: Quote;
    file_name: string;
    notes: string;
    machine_id: string;
    width: number;
    length: number;
    quantity: number;
}

const emptyItem = {
    product_id: '',
    material_id: '',
    machine_id: '',
    quantity: 1,
    width: 1,
    length: 1,
    file_name: '',
    notes: '',
};

export function DesignerNewOrderPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const optionsQuery = useQuery({ queryKey: ['quote-options'], queryFn: api.quoteOptions });

    const [customer, setCustomer] = useState<Customer | null>(null);
    const [customerForm, setCustomerForm] = useState({ name: '', phone: '', type: 'Reguler' });
    const [customerErrors, setCustomerErrors] = useState<Record<string, string>>({});
    const [item, setItem] = useState(emptyItem);
    const [quote, setQuote] = useState<Quote | null>(null);
    const [cart, setCart] = useState<CartLine[]>([]);
    const [notice, setNotice] = useState<string | null>(null);
    const [submitErrors, setSubmitErrors] = useState<Record<string, string>>({});

    const options = optionsQuery.data;

    const createCustomer = useMutation({
        mutationFn: () => api.createCustomer(customerForm),
        onSuccess: (response) => {
            setCustomer(response.customer);
            setCustomerErrors({});
            setNotice('Pelanggan dibuat dan dipilih.');
        },
        onError: (error) => {
            setCustomerErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const calculate = useMutation({
        mutationFn: () =>
            api.quote({
                product_id: item.product_id,
                material_id: item.material_id,
                machine_id: item.machine_id || null,
                quantity: item.quantity,
                width: item.width,
                length: item.length,
            }),
        onSuccess: (response) => setQuote(response.quote),
        onError: (error) => {
            setQuote(null);
            setNotice(errorMessage(error));
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
                deadline: null,
                source: 'Offline',
                items: cart.map((line) => ({
                    pricing_id: line.quote.pricing_id,
                    file_name: line.file_name,
                    notes: line.notes,
                    quantity: line.quantity,
                    width: line.width,
                    length: line.length,
                    machine_id: line.machine_id || null,
                    is_manual_price: false,
                })),
            });
        },
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['designer', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice(`Invoice ${response.invoice.invoice_number} dibuat.`);
            setCart([]);
            setQuote(null);
            setItem(emptyItem);
            navigate('/designer/orders');
        },
        onError: (error) => {
            setSubmitErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const addLine = () => {
        if (!quote) {
            setNotice('Hitung penawaran sebelum menambah item.');
            return;
        }

        setCart((current) => [
            ...current,
            {
                quote,
                file_name: item.file_name,
                notes: item.notes,
                machine_id: item.machine_id,
                width: item.width,
                length: item.length,
                quantity: item.quantity,
            },
        ]);
        setQuote(null);
        setItem({ ...emptyItem, file_name: '', notes: '' });
    };

    const removeLine = (index: number) => {
        setCart((current) => current.filter((_, position) => position !== index));
    };

    const cartTotal = cart.reduce((total, line) => total + Number(line.quote.subtotal), 0);

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Desainer"
                title="Pesanan Baru"
                description="Buat pelanggan, atur item, pratinjau harga, lalu kirim invoice."
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
                <div className="min-w-0 space-y-6">
                    <Card className="min-w-0">
                        <CardHeader>
                            <h2 className="font-semibold">Pelanggan</h2>
                        </CardHeader>
                        <CardBody className="space-y-4">
                            {customer ? (
                                <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-light-border bg-light-surface-2 px-4 py-3 dark:border-surface-300 dark:bg-surface-600">
                                    <div className="min-w-0">
                                        <p className="font-medium">{customer.name}</p>
                                        <p className="text-light-muted-strong text-sm dark:text-gray-400">
                                            {customer.phone ?? 'Tanpa telepon'} · {customer.type ?? 'Reguler'}
                                        </p>
                                    </div>
                                    <Button variant="secondary" size="sm" onClick={() => setCustomer(null)}>
                                        Ubah
                                    </Button>
                                </div>
                            ) : (
                                <>
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <Input
                                            label="Nama"
                                            value={customerForm.name}
                                            error={customerErrors.name}
                                            onChange={(event) =>
                                                setCustomerForm({ ...customerForm, name: event.target.value })
                                            }
                                        />
                                        <Input
                                            label="Telepon"
                                            value={customerForm.phone}
                                            error={customerErrors.phone}
                                            onChange={(event) =>
                                                setCustomerForm({ ...customerForm, phone: event.target.value })
                                            }
                                        />
                                        <Select
                                            label="Tipe"
                                            value={customerForm.type}
                                            onChange={(event) =>
                                                setCustomerForm({ ...customerForm, type: event.target.value })
                                            }
                                        >
                                            <option value="Reguler">Reguler</option>
                                            <option value="Reseller">Reseller</option>
                                        </Select>
                                    </div>
                                    <Button
                                        size="sm"
                                        loading={createCustomer.isPending}
                                        onClick={() => createCustomer.mutate()}
                                    >
                                        Buat pelanggan
                                    </Button>
                                </>
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
                                            onChange={(event) =>
                                                setItem({ ...item, product_id: event.target.value })
                                            }
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
                                                setItem({ ...item, material_id: event.target.value })
                                            }
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
                                            value={item.machine_id}
                                            onChange={(event) =>
                                                setItem({ ...item, machine_id: event.target.value })
                                            }
                                        >
                                            <option value="">Tanpa mesin</option>
                                            {(options?.machines ?? []).map((machine) => (
                                                <option key={machine.id} value={machine.id}>
                                                    {machine.name}
                                                </option>
                                            ))}
                                        </Select>
                                        <Input
                                            label="Jml"
                                            type="number"
                                            min="1"
                                            value={item.quantity}
                                            onChange={(event) =>
                                                setItem({ ...item, quantity: Number(event.target.value) })
                                            }
                                        />
                                        <Input
                                            label="Lebar (m)"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={item.width}
                                            onChange={(event) =>
                                                setItem({ ...item, width: Number(event.target.value) })
                                            }
                                        />
                                        <Input
                                            label="Panjang (m)"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={item.length}
                                            onChange={(event) =>
                                                setItem({ ...item, length: Number(event.target.value) })
                                            }
                                        />
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

                                    <div className="rounded-xl border border-light-border bg-light-surface-2/60 px-4 py-3 dark:border-surface-300 dark:bg-surface-600/40">
                                        <div className="flex flex-wrap items-center justify-between gap-4">
                                            <dl className="flex flex-wrap items-baseline gap-x-6 gap-y-2">
                                                <div>
                                                    <dt className="text-light-muted text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400">
                                                        Subtotal
                                                    </dt>
                                                    <dd className="text-light-text text-lg font-extrabold tabular-nums dark:text-gray-100">
                                                        {quote ? formatMoney(quote.subtotal) : '-'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-light-muted text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400">
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
                                            <TableCell>{line.file_name || `Item ${index + 1}`}</TableCell>
                                            <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                {line.quantity}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums whitespace-nowrap">
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
                            <tfoot className="border-t border-light-border dark:border-surface-300">
                                <TableRow>
                                    <td
                                        colSpan={2}
                                        className="text-light-text px-3 py-2 text-right align-middle font-semibold dark:text-gray-100"
                                    >
                                        Total
                                    </td>
                                    <td
                                        colSpan={2}
                                        className="text-brand-fill dark:text-brand-primary-light px-3 py-2 text-right align-middle text-lg font-bold tabular-nums whitespace-nowrap"
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

            {notice ? <Toast message={notice} onClose={() => setNotice(null)} /> : null}
        </div>
    );
}
