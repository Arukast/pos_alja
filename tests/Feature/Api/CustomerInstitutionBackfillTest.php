<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Support\CustomerInstitution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/** Feature coverage for institution backfills replayed through the real migrations. */
final class CustomerInstitutionBackfillTest extends TestCase
{
    use RefreshDatabase;

    /** A customer created without labels receives the literal JSON column default. */
    public function test_new_customer_gets_default_institution(): void
    {
        $customer = Customer::create(['name' => 'Default Label']);

        $this->assertSame(['Masyarakat/Retail'], $customer->fresh()->institutions);
    }

    /** Replaying the labels migration wraps each legacy primary label in a single-element list. */
    public function test_backfill_wraps_each_existing_primary_label(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 7]);

        DB::table('customers')->insert([
            $this->legacyCustomer('Sekolah Contoh', 'Sekolah'),
            $this->legacyCustomer('Universitas Contoh', 'Universitas'),
            $this->legacyCustomer('Retail Contoh', 'Masyarakat/Retail'),
        ]);

        Artisan::call('migrate');

        $this->assertSame(['Sekolah'], $this->institutionsOf('Sekolah Contoh'));
        $this->assertSame(['Universitas'], $this->institutionsOf('Universitas Contoh'));
        $this->assertSame(['Masyarakat/Retail'], $this->institutionsOf('Retail Contoh'));
    }

    /** Unknown legacy labels are preserved verbatim instead of being dropped. */
    public function test_backfill_preserves_legacy_unknown_label(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 7]);

        DB::table('customers')->insert(
            $this->legacyCustomer('Legacy Contoh', 'Lainnya')
        );

        Artisan::call('migrate');

        $this->assertSame(['Lainnya'], $this->institutionsOf('Legacy Contoh'));
    }

    /** The replayed backfill covers every row across more than one chunk. */
    public function test_backfill_handles_more_than_one_chunk(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 7]);

        $customers = [];

        for ($i = 0; $i < 205; $i++) {
            $customers[] = $this->legacyCustomer(sprintf('Pelanggan %03d', $i), 'Sekolah');
        }

        DB::table('customers')->insert($customers);

        Artisan::call('migrate');

        $this->assertSame(['Sekolah'], $this->institutionsOf('Pelanggan 000'));
        $this->assertSame(['Sekolah'], $this->institutionsOf('Pelanggan 102'));
        $this->assertSame(['Sekolah'], $this->institutionsOf('Pelanggan 204'));
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

        Artisan::call('migrate:rollback', ['--step' => 6]);

        $invoice = $this->legacyInvoice($customer->id);
        DB::table('invoices')->insert($invoice);

        Artisan::call('migrate');

        $this->assertSame('Sekolah', $this->invoiceInstitution($invoice['invoice_number']));
    }

    /** A soft-deleted customer still supplies the label for its historical invoices. */
    public function test_invoice_backfill_includes_soft_deleted_customers(): void
    {
        $customer = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ]);
        $customer->delete();

        Artisan::call('migrate:rollback', ['--step' => 6]);

        $invoice = $this->legacyInvoice($customer->id);
        DB::table('invoices')->insert($invoice);

        Artisan::call('migrate');

        $this->assertSame('Sekolah', $this->invoiceInstitution($invoice['invoice_number']));
    }

    /** The replayed invoice backfill spans more than one chunk. */
    public function test_invoice_backfill_is_chunked(): void
    {
        $sekolah = Customer::create([
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ]);
        $pemerintahan = Customer::create([
            'name' => 'Pemerintah Contoh',
            'institution' => 'Pemerintahan',
        ]);

        Artisan::call('migrate:rollback', ['--step' => 6]);

        $invoices = [];

        for ($i = 0; $i < 205; $i++) {
            $invoices[] = $this->legacyInvoice($i % 2 === 0 ? $sekolah->id : $pemerintahan->id, $i);
        }

        DB::table('invoices')->insert($invoices);

        Artisan::call('migrate');

        $this->assertSame('Sekolah', $this->invoiceInstitution($invoices[0]['invoice_number']));
        $this->assertSame('Pemerintahan', $this->invoiceInstitution($invoices[101]['invoice_number']));
        $this->assertSame('Sekolah', $this->invoiceInstitution($invoices[204]['invoice_number']));
    }

    /**
     * @return array{id: string, name: string, institution: string, created_at: string, updated_at: string, deleted_at?: string}
     */
    private function legacyCustomer(
        string $name,
        string $institution,
        ?string $id = null,
        ?string $deletedAt = null
    ): array {
        $now = now()->toDateTimeString();
        $row = [
            'id' => $id ?? (string) Str::uuid(),
            'name' => $name,
            'institution' => $institution,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($deletedAt !== null) {
            $row['deleted_at'] = $deletedAt;
        }

        return $row;
    }

    /**
     * @return array{id: string, invoice_number: string, created_date: string, customer_id: string, created_at: string, updated_at: string}
     */
    private function legacyInvoice(string $customerId, ?int $sequence = null): array
    {
        $now = now()->toDateTimeString();

        return [
            'id' => (string) Str::uuid(),
            'invoice_number' => sprintf('INV-BF-%s-%s', $sequence ?? random_int(0, PHP_INT_MAX), Str::upper(Str::random(6))),
            'created_date' => $now,
            'customer_id' => $customerId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @return list<string> */
    private function institutionsOf(string $name): array
    {
        $json = DB::table('customers')->where('name', $name)->value('institutions');
        $this->assertIsString($json);

        /** @var list<string> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function invoiceInstitution(string $invoiceNumber): ?string
    {
        $institution = DB::table('invoices')
            ->where('invoice_number', $invoiceNumber)
            ->value('institution');

        return $institution === null ? null : (string) $institution;
    }
}
