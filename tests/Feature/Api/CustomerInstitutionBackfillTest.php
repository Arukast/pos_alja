<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Support\CustomerInstitution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/** Feature coverage for customer institution backfill and normalization. */
final class CustomerInstitutionBackfillTest extends TestCase
{
    use RefreshDatabase;

    /** A customer created without labels receives the literal JSON column default. */
    public function test_new_customer_gets_default_institution(): void
    {
        $customer = Customer::create(['name' => 'Default Label']);

        $this->assertSame(['Masyarakat/Retail'], $customer->fresh()->institutions);
    }

    /** The backfill wraps each legacy primary label in a single-element list. */
    public function test_backfill_wraps_each_existing_primary_label(): void
    {
        $sekolah = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
            'institutions' => null,
        ]);
        $universitas = Customer::create([
            'name' => 'Universitas Contoh',
            'institution' => 'Universitas',
            'institutions' => null,
        ]);
        $default = Customer::create([
            'name' => 'Retail Contoh',
            'institutions' => null,
        ]);

        CustomerInstitution::backfillCustomers();

        $this->assertSame(['Sekolah'], $sekolah->fresh()->institutions);
        $this->assertSame(['Universitas'], $universitas->fresh()->institutions);
        $this->assertSame(['Masyarakat/Retail'], $default->fresh()->institutions);
    }

    /** Unknown legacy labels are preserved verbatim instead of being dropped. */
    public function test_backfill_preserves_legacy_unknown_label(): void
    {
        $customer = Customer::create([
            'name' => 'Legacy Contoh',
            'institution' => 'Lainnya',
            'institutions' => null,
        ]);

        CustomerInstitution::backfillCustomers();

        $this->assertSame(['Lainnya'], $customer->fresh()->institutions);
    }

    /** The backfill covers every row across more than one chunk. */
    public function test_backfill_handles_more_than_one_chunk(): void
    {
        $customers = [];

        for ($i = 0; $i < 205; $i++) {
            $customers[] = Customer::create([
                'name' => sprintf('Pelanggan %03d', $i),
                'institution' => 'Sekolah',
                'institutions' => null,
            ]);
        }

        CustomerInstitution::backfillCustomers();

        $this->assertSame(['Sekolah'], $customers[0]->fresh()->institutions);
        $this->assertSame(['Sekolah'], $customers[102]->fresh()->institutions);
        $this->assertSame(['Sekolah'], $customers[204]->fresh()->institutions);
    }

    /** Normalization dedupes, orders by the canonical list, and rejects unknown labels. */
    public function test_normalize_sorts_and_dedupes_by_constant_order(): void
    {
        $this->assertSame(
            ['Masyarakat/Retail', 'Sekolah'],
            CustomerInstitution::normalize(['Sekolah', 'Masyarakat/Retail', 'Sekolah'])
        );
        $this->assertSame(['Organisasi/Yayasan'], CustomerInstitution::normalize(['Organisasi/Yayasan']));

        $this->expectException(InvalidArgumentException::class);
        CustomerInstitution::normalize(['Lainnya']);
    }

    /** Historical invoices take the current primary label of their customer. */
    public function test_invoice_backfill_uses_current_customer_label(): void
    {
        $customer = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ]);
        $invoice = $this->invoice($customer);

        CustomerInstitution::backfillInvoices();

        $this->assertSame('Sekolah', $invoice->fresh()->institution);
    }

    /** A soft-deleted customer still supplies the label for its historical invoices. */
    public function test_invoice_backfill_includes_soft_deleted_customers(): void
    {
        $customer = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ]);
        $invoice = $this->invoice($customer);

        $customer->delete();

        CustomerInstitution::backfillInvoices();

        $this->assertSame('Sekolah', $invoice->fresh()->institution);
    }

    /** The invoice backfill spans more than one chunk and never overwrites an existing snapshot. */
    public function test_invoice_backfill_is_chunked_and_skips_rows_with_a_snapshot(): void
    {
        $sekolah = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ]);
        $pemerintahan = Customer::create([
            'name' => 'Pemerintah Contoh',
            'institution' => 'Pemerintahan',
        ]);

        $invoices = [];

        for ($i = 0; $i < 205; $i++) {
            $invoices[] = $this->invoice($i % 2 === 0 ? $sekolah : $pemerintahan);
        }

        $snapshotted = $this->invoice($sekolah, 'Universitas');

        CustomerInstitution::backfillInvoices();

        $this->assertSame('Sekolah', $invoices[0]->fresh()->institution);
        $this->assertSame('Pemerintahan', $invoices[101]->fresh()->institution);
        $this->assertSame('Sekolah', $invoices[204]->fresh()->institution);
        $this->assertSame('Universitas', $snapshotted->fresh()->institution);

        CustomerInstitution::backfillInvoices();

        $this->assertSame('Universitas', $snapshotted->fresh()->institution);
    }

    /**
     * Create a minimal invoice eligible for the historical backfill.
     *
     * @param  string|null  $institution  pre-existing snapshot, left untouched by the backfill
     */
    private function invoice(Customer $customer, ?string $institution = null): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-BF-'.Str::upper(Str::random(8)),
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'institution' => $institution,
        ]);
    }
}
