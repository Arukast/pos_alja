import { api } from '../../api/queries';
import { CUSTOMER_INSTITUTIONS, type Customer } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { canEditCustomer } from './customerAccess';

const DEFAULT_INSTITUTION = 'Masyarakat/Retail';
const KNOWN_INSTITUTIONS = new Set<string>(CUSTOMER_INSTITUTIONS);

/** Drop legacy labels the API rejects on write, keeping at least one valid label. */
function institutionValues(customer: Customer): string[] {
    const known = customer.institutions.filter((label) => KNOWN_INSTITUTIONS.has(label));

    return known.length > 0 ? known : [DEFAULT_INSTITUTION];
}

export function CustomerManagementPage() {
    const { user } = useAuth();
    const editable = canEditCustomer(user?.role);

    const fields: Array<CrudField<Customer>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'institutions',
            label: 'Institusi',
            type: 'multiselect',
            required: true,
            defaultValue: [DEFAULT_INSTITUTION],
            options: CUSTOMER_INSTITUTIONS.map((label) => ({ value: label, label })),
            getValue: institutionValues,
        },
        {
            key: 'type',
            label: 'Tipe',
            type: 'select',
            defaultValue: 'Reguler',
            options: [
                { value: 'Reguler', label: 'Reguler' },
                { value: 'Reseller', label: 'Reseller' },
            ],
            getValue: (customer) => customer.type ?? '',
        },
        { key: 'phone', label: 'Telepon', getValue: (customer) => customer.phone ?? '' },
        { key: 'email', label: 'Email', type: 'email', getValue: (customer) => customer.email ?? '' },
        { key: 'address', label: 'Alamat', getValue: (customer) => customer.address ?? '' },
    ];

    return (
        <CrudPage<Customer>
            eyebrow="Manajemen"
            title="Manajemen Pelanggan"
            description="Daftar pelanggan dan institusinya."
            queryKey={['customers']}
            queryFn={(page) => api.customers({ page })}
            columns={[
                { header: 'Nama', cell: (customer) => customer.name },
                { header: 'Institusi', cell: (customer) => customer.institutions.join(', ') },
                { header: 'Tipe', cell: (customer) => customer.type ?? '-' },
                { header: 'Telepon', cell: (customer) => customer.phone ?? '-' },
            ]}
            fields={fields}
            create={editable ? (values) => api.createCustomer(values) : undefined}
            update={editable ? (id, values) => api.updateCustomer(id, values) : undefined}
            canEditRow={() => editable}
            createLabel="Tambah Pelanggan"
        />
    );
}
