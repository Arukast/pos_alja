import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useNavigate } from 'react-router';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { errorMessage, mapApiErrors } from '../../lib/errors';

/** Dedicated customer registration page used before starting an order. */
export function DesignerNewCustomerPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [form, setForm] = useState({
        name: '',
        phone: '',
        email: '',
        address: '',
        institution: 'Masyarakat/Retail',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const createCustomer = useMutation({
        mutationFn: () => api.createCustomer({
            ...form,
            name: form.name.trim(),
            phone: form.phone.trim() || null,
            email: form.email.trim() || null,
            address: form.address.trim() || null,
        }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['customers'] });
            navigate('/designer/new-order');
        },
        onError: (error) => setErrors({ ...mapApiErrors(error), form: errorMessage(error) }),
    });

    /** Submits the customer form and displays server validation errors. */
    const handleSubmit = () => {
        setErrors({});
        createCustomer.mutate();
    };

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Desainer"
                title="Pelanggan Baru"
                description="Nama dan institusi wajib diisi. Informasi kontak bersifat opsional."
            />
            <Card>
                <CardBody className="space-y-4">
                    <Input label="Nama" value={form.name} error={errors.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                    <Select label="Institusi" value={form.institution} error={errors.institution} onChange={(event) => setForm({ ...form, institution: event.target.value })}>
                        <option value="Masyarakat/Retail">Masyarakat/Retail</option>
                        <option value="Sekolah">Sekolah</option>
                        <option value="Universitas">Universitas</option>
                        <option value="Pemerintahan">Pemerintahan</option>
                    </Select>
                    <Input label="Nomor Telepon" value={form.phone} error={errors.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
                    <Input label="Email" value={form.email} error={errors.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
                    <Input label="Alamat" value={form.address} error={errors.address} onChange={(event) => setForm({ ...form, address: event.target.value })} />
                    {errors.form ? <p role="alert" className="text-sm text-red-600">{errors.form}</p> : null}
                    <div className="flex gap-3">
                        <Button variant="secondary" onClick={() => navigate(-1)}>Batal</Button>
                        <Button loading={createCustomer.isPending} onClick={handleSubmit}>Simpan pelanggan</Button>
                    </div>
                </CardBody>
            </Card>
        </div>
    );
}
