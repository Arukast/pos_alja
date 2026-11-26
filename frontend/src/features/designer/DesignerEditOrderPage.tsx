import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import { api } from '../../api/queries';
import type { Invoice, Order } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

interface EditableOrderItem {
    id?: string;
    pricing_id: string;
    file_name: string;
    notes: string;
    quantity: string;
    width: string;
    length: string;
    selected_attributes: string[];
}

/** Creates a safe draft from a persisted order, preserving dimensions and selected attributes. */
function editableItem(order: Order): EditableOrderItem {
    const width = order.width ?? '1';
    const length = order.length ?? (Number(order.area) > 0 ? order.area : '1');

    return {
        id: order.id,
        pricing_id: order.pricing_id ?? '',
        file_name: order.file_name ?? '',
        notes: order.notes ?? '',
        quantity: String(order.quantity),
        width,
        length,
        selected_attributes: order.selected_attributes ?? [],
    };
}

/** Edits all invoice items before production and while payment remains outstanding. */
export function DesignerEditOrderPage() {
    const { id = '' } = useParams();
    const invoiceQuery = useQuery({ queryKey: ['invoice', 'detail', id], queryFn: () => api.invoice(id), enabled: id !== '' });
    const invoice = invoiceQuery.data?.invoice;

    const canEdit = invoice !== undefined
        && invoice.status !== 2
        && (invoice.orders ?? []).every((order) => order.status === 0 && order.pricing_id !== null);

    if (invoiceQuery.isLoading) {
        return <div role="status" className="flex min-h-48 items-center justify-center"><Spinner className="text-brand-primary h-6 w-6" /></div>;
    }

    if (invoiceQuery.isError || !invoice) {
        return <EmptyState title="Invoice tidak dapat dimuat" description="Periksa koneksi dan hak akses, lalu coba lagi." action={<Button variant="secondary" onClick={() => invoiceQuery.refetch()}>Coba lagi</Button>} />;
    }

    if (!canEdit) {
        return <EmptyState title="Invoice tidak dapat diubah" description="Invoice sudah lunas, item sudah masuk produksi, atau data tarif item belum tersedia untuk dihitung ulang." action={<Link className="text-brand-fill-hover font-semibold hover:underline" to="/designer/orders">Kembali ke pesanan</Link>} />;
    }

    return <DesignerEditOrderForm key={invoice.id} invoice={invoice} invoiceId={id} />;
}

/** Holds the editable draft after its invoice is loaded from the server. */
function DesignerEditOrderForm({ invoice, invoiceId }: { invoice: Invoice; invoiceId: string }) {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [items, setItems] = useState<EditableOrderItem[]>(() => (invoice.orders ?? []).map(editableItem));
    const [deadline, setDeadline] = useState(invoice.deadline ?? '');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);
    const optionsQuery = useQuery({ queryKey: ['quote-options'], queryFn: api.quoteOptions });

    const update = useMutation({
        mutationFn: () => api.updateInvoiceItems(invoiceId, {
            deadline: deadline || null,
            items: items.map((item) => ({
                ...item,
                quantity: Number(item.quantity),
                width: Number(item.width) > 0 ? Number(item.width) : undefined,
                length: Number(item.length) > 0 ? Number(item.length) : undefined,
            })),
        }),
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['invoice', 'detail', invoiceId] });
            void queryClient.invalidateQueries({ queryKey: ['invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['designer', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice(`Invoice diperbarui. Total baru ${formatMoney(response.invoice.grand_total)}.`);
            navigate('/designer/orders');
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    /** Changes one editable order row without changing the other rows. */
    const handleItemChange = (index: number, patch: Partial<EditableOrderItem>) => {
        setItems((current) => current.map((item, itemIndex) =>
            itemIndex === index ? { ...item, ...patch } : item
        ));
    };

    /** Replaces only one selected attribute type, preserving the other single-select option. */
    const handleAttributeChange = (index: number, type: 'finishing' | 'display', value: string) => {
        const current = items[index];
        const retained = current.selected_attributes.filter((attributeId) =>
            optionsQuery.data?.attributes.find((attribute) => attribute.id === attributeId)?.type !== type
        );
        handleItemChange(index, { selected_attributes: value ? [...retained, value] : retained });
    };

    /** Adds a new line using an existing invoice price configuration as its starting point. */
    const handleAddItem = () => {
        const template = items[0];

        if (!template) {
            return;
        }

        const { id: _id, ...copy } = template;
        setItems((current) => [...current, { ...copy, file_name: '', notes: '', quantity: '1' }]);
    };

    /** Removes one line while keeping at least one line for a valid invoice. */
    const handleRemoveItem = (index: number) => {
        setItems((current) => current.length > 1
            ? current.filter((_, itemIndex) => itemIndex !== index)
            : current
        );
    };

    /** Sends all desired invoice items for server-side quote recalculation. */
    const handleSave = () => {
        setErrors({});
        setNotice(null);
        update.mutate();
    };

    if (optionsQuery.isLoading) {
        return <div role="status" className="flex min-h-48 items-center justify-center"><Spinner className="text-brand-primary h-6 w-6" /></div>;
    }

    if (optionsQuery.isError) {
        return <EmptyState title="Opsi item gagal dimuat" description="Periksa koneksi, lalu coba lagi." action={<Button variant="secondary" onClick={() => optionsQuery.refetch()}>Coba lagi</Button>} />;
    }

    const pricingOptions = Array.from(new Map((invoice.orders ?? [])
        .filter((order) => order.pricing_id !== null)
        .map((order) => [order.pricing_id as string, {
            id: order.pricing_id as string,
            label: [order.pricing?.product, order.pricing?.material].filter(Boolean).join(' · ') || 'Konfigurasi item',
        }])).values());
    const attributes = optionsQuery.data?.attributes ?? [];

    return (
        <div className="space-y-6">
            <PageBanner eyebrow="Desainer" title={`Ubah ${invoice.invoice_number}`} description="Item dapat diubah selama invoice belum lunas dan produksi belum dimulai. Harga dan sisa tagihan dihitung ulang oleh server." />
            <Card>
                <CardHeader><h2 className="font-semibold">Detail tagihan saat ini</h2></CardHeader>
                <CardBody className="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    <span>Total: <strong>{formatMoney(invoice.grand_total)}</strong></span>
                    <span>Sudah dibayar: <strong>{formatMoney(Number(invoice.grand_total) - Number(invoice.accounts_receivable))}</strong></span>
                    <span>Sisa: <strong>{formatMoney(invoice.accounts_receivable)}</strong></span>
                </CardBody>
            </Card>
            <Card>
                <CardHeader><h2 className="font-semibold">Item pesanan</h2><Button variant="secondary" size="sm" onClick={handleAddItem}>Tambah item</Button></CardHeader>
                <CardBody className="space-y-5">
                    {items.map((item, index) => (
                        <section key={item.id ?? `new-${index}`} className="space-y-3 border-b border-light-border pb-5 last:border-0 dark:border-surface-300">
                            <div className="flex items-center justify-between"><h3 className="font-medium">Item {index + 1}</h3><Button variant="ghost" size="sm" disabled={items.length <= 1} onClick={() => handleRemoveItem(index)}>Hapus</Button></div>
                            <Select label="Produk dan bahan" value={item.pricing_id} error={errors[`items.${index}.pricing_id`]} onChange={(event) => handleItemChange(index, { pricing_id: event.target.value, selected_attributes: [] })}>
                                {pricingOptions.map((configuration) => <option key={configuration.id} value={configuration.id}>{configuration.label}</option>)}
                            </Select>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Input label="Nama file / item" value={item.file_name} error={errors[`items.${index}.file_name`]} onChange={(event) => handleItemChange(index, { file_name: event.target.value })} />
                                <Input label="Jumlah" type="number" min="0.01" step="0.01" value={item.quantity} error={errors[`items.${index}.quantity`]} onChange={(event) => handleItemChange(index, { quantity: event.target.value })} />
                                <Input label="Lebar (m)" type="number" min="0.01" step="0.01" value={item.width} error={errors[`items.${index}.width`]} onChange={(event) => handleItemChange(index, { width: event.target.value })} />
                                <Input label="Panjang (m)" type="number" min="0.01" step="0.01" value={item.length} error={errors[`items.${index}.length`]} onChange={(event) => handleItemChange(index, { length: event.target.value })} />
                                <Select label="Finishing" value={attributes.find((attribute) => attribute.type === 'finishing' && item.selected_attributes.includes(attribute.id))?.id ?? ''} onChange={(event) => handleAttributeChange(index, 'finishing', event.target.value)}>
                                    <option value="">Tanpa finishing</option>{attributes.filter((attribute) => attribute.type === 'finishing').map((attribute) => <option key={attribute.id} value={attribute.id}>{attribute.name}</option>)}
                                </Select>
                                <Select label="Display" value={attributes.find((attribute) => attribute.type === 'display' && item.selected_attributes.includes(attribute.id))?.id ?? ''} onChange={(event) => handleAttributeChange(index, 'display', event.target.value)}>
                                    <option value="">Tanpa display</option>{attributes.filter((attribute) => attribute.type === 'display').map((attribute) => <option key={attribute.id} value={attribute.id}>{attribute.name}</option>)}
                                </Select>
                                <Input label="Catatan" value={item.notes} error={errors[`items.${index}.notes`]} onChange={(event) => handleItemChange(index, { notes: event.target.value })} />
                            </div>
                        </section>
                    ))}
                    <Input label="Tenggat" type="date" value={deadline} error={errors.deadline} onChange={(event) => setDeadline(event.target.value)} />
                    {notice ? <p role="alert" className="text-sm text-red-600">{notice}</p> : null}
                    {errors.items ? <p role="alert" className="text-sm text-red-600">{errors.items}</p> : null}
                    <div className="flex flex-wrap gap-3"><Button variant="secondary" onClick={() => navigate('/designer/orders')}>Batal</Button><Button loading={update.isPending} onClick={handleSave}>Simpan perubahan</Button></div>
                </CardBody>
            </Card>
        </div>
    );
}
