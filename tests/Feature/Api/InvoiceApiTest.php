<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengujian fitur API manajemen pemesanan, penawaran, visibilitas uang, dan pengambilan invoice (PB-02).
 */
class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private User $otherCashier;

    private Customer $customer;

    private Invoice $invoiceOne;

    private Invoice $invoiceTwo;

    /**
     * Menyiapkan dua cabang, seluruh peran pengguna, pelanggan, invoice, pesanan, dan pembayaran awal.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->designer = $this->user('designer', $this->branchOne);
        $this->production = $this->user('production', $this->branchOne);
        $this->otherCashier = $this->user('cashier', $this->branchTwo);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $this->invoiceOne = $this->invoice($this->branchOne, [
            'invoice_number' => 'INV-B1-001',
            'status' => 1,
        ]);
        $this->invoiceTwo = $this->invoice($this->branchTwo, [
            'invoice_number' => 'INV-B2-001',
            'status' => 1,
        ]);

        $this->order($this->invoiceOne);
        $this->order($this->invoiceTwo);

        $this->payment($this->invoiceOne, $this->branchOne, $this->cashier);
        $this->payment($this->invoiceTwo, $this->branchTwo, $this->otherCashier);
    }

    /**
     * Memastikan tamu yang belum login ditolak dengan status 401 pada seluruh endpoint invoice dan penawaran.
     */
    public function test_guests_receive_401_for_invoice_and_quote_endpoints(): void
    {
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->postJson('/api/v1/invoices', [])->assertUnauthorized();
        $this->getJson("/api/v1/invoices/{$this->invoiceOne->id}")->assertUnauthorized();
        $this->getJson("/api/v1/invoices/{$this->invoiceOne->id}/ticket")->assertUnauthorized();
        $this->getJson("/api/v1/invoices/{$this->invoiceOne->id}/receipt")->assertUnauthorized();
        $this->getJson('/api/v1/quotes/options')->assertUnauthorized();
        $this->postJson('/api/v1/quotes', [])->assertUnauthorized();
    }

    /**
     * Memastikan pencarian pelanggan mencocokkan nama dan nomor telepon untuk kasir dan desainer.
     */
    public function test_customer_search_matches_name_and_phone_for_cashier_and_designer(): void
    {
        foreach ([$this->cashier, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson('/api/v1/customers?search=Jane')
                ->assertOk()
                ->assertJsonFragment(['id' => $this->customer->id]);

            $this->actingAs($user)
                ->getJson('/api/v1/customers?search=081234')
                ->assertOk()
                ->assertJsonFragment(['id' => $this->customer->id]);
        }
    }

    /**
     * Memastikan peran produksi dilarang mencari data pelanggan.
     */
    public function test_production_cannot_search_customers(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/customers?search=Jane')
            ->assertForbidden();
    }

    /**
     * Memastikan daftar invoice dibatasi pada cabang pengguna non-owner dan terbuka penuh untuk owner.
     */
    public function test_invoice_list_is_branch_scoped(): void
    {
        $cashierResponse = $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices')
            ->assertOk();

        $cashierIds = collect($cashierResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->invoiceOne->id, $cashierIds);
        $this->assertNotContains($this->invoiceTwo->id, $cashierIds);

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices')
            ->assertOk();

        $ownerIds = collect($ownerResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->invoiceOne->id, $ownerIds);
        $this->assertContains($this->invoiceTwo->id, $ownerIds);
    }

    /**
     * Memastikan owner dapat memfilter cabang, sedangkan pengguna cabang tidak dapat menembus cakupan cabangnya.
     */
    public function test_owner_can_filter_invoices_by_branch_while_non_owner_cannot_escape_branch(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/v1/invoices?branch_id={$this->branchTwo->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceTwo->id);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?branch_id=all')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices?branch_id={$this->branchTwo->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);
    }

    /**
     * Memastikan pengguna non-owner tanpa cabang ditolak dan tidak diperlakukan sebagai akses global.
     */
    public function test_non_owner_without_branch_is_denied_invoice_and_quote_access(): void
    {
        $homelessDesigner = $this->user('designer', null);

        $this->actingAs($homelessDesigner)
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($homelessDesigner)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertForbidden();

        $this->actingAs($homelessDesigner)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/ticket")
            ->assertForbidden();

        $this->actingAs($homelessDesigner)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/receipt")
            ->assertForbidden();

        $this->actingAs($homelessDesigner)
            ->getJson('/api/v1/quotes/options')
            ->assertForbidden();

        $this->actingAs($homelessDesigner)
            ->postJson('/api/v1/quotes', [
                'product_id' => (string) Str::uuid(),
                'material_id' => (string) Str::uuid(),
                'quantity' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($homelessDesigner)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => (string) Str::uuid(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertForbidden();
    }

    /**
     * Memastikan daftar invoice bagi produksi menyembunyikan seluruh kolom finansial.
     */
    public function test_invoice_list_is_available_to_production_without_money(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonMissingPath('data.0.grand_total')
            ->assertJsonMissingPath('data.0.total_cost')
            ->assertJsonMissingPath('data.0.total_profit')
            ->assertJsonMissingPath('data.0.discount')
            ->assertJsonMissingPath('data.0.dp_amount')
            ->assertJsonMissingPath('data.0.accounts_receivable')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);
    }

    /**
     * Memastikan detail invoice memuat pelanggan, pesanan, pembayaran, serta menyembunyikan HPP dan profit bagi kasir.
     */
    public function test_invoice_detail_includes_customer_orders_and_payments(): void
    {
        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceOne->id)
            ->assertJsonPath('invoice.customer.id', $this->customer->id)
            ->assertJsonPath('invoice.orders.0.file_name', 'banner.pdf')
            ->assertJsonPath('invoice.payments.0.amount', '50000.00')
            ->assertJsonPath('invoice.grand_total', '100000.00')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.orders.0.unit_price')
            ->assertJsonMissingPath('invoice.orders.0.subtotal')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');
    }

    /**
     * Memastikan desainer melihat harga jual tetapi tidak melihat biaya maupun profit.
     */
    public function test_designer_sees_sell_price_but_not_cost_or_profit(): void
    {
        $this->actingAs($this->designer)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.unit_price', '50000.00')
            ->assertJsonPath('invoice.orders.0.subtotal', '100000.00')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit');
    }

    /**
     * Memastikan owner dan manajer dapat melihat total biaya dan profit.
     */
    public function test_owner_and_manager_see_cost_and_profit(): void
    {
        foreach ([$this->owner, $this->manager] as $user) {
            $this->actingAs($user)
                ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
                ->assertOk()
                ->assertJsonPath('invoice.total_cost', '60000.00')
                ->assertJsonPath('invoice.total_profit', '40000.00')
                ->assertJsonPath('invoice.orders.0.cost', '60000.00')
                ->assertJsonPath('invoice.orders.0.profit', '40000.00');
        }
    }

    /**
     * Memastikan detail invoice memuat label harga dan archetype untuk form edit desainer.
     */
    public function test_invoice_detail_includes_order_identity_snapshots(): void
    {
        $fixture = $this->catalogFixture($this->branchOne);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'material_id' => $fixture['material']->id,
                        'quantity' => 1,
                        'width' => 2,
                        'length' => 1,
                        'is_manual_price' => false,
                    ],
                ],
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/invoices/'.$created->json('invoice.id'))
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.product_id', $fixture['product']->id)
            ->assertJsonPath('invoice.orders.0.product', 'Spanduk Flexy')
            ->assertJsonPath('invoice.orders.0.material_id', $fixture['material']->id)
            ->assertJsonPath('invoice.orders.0.material', 'Flexy 340gr')
            ->assertJsonMissingPath('invoice.orders.0.pricing');
    }

    /**
     * Memastikan detail invoice tetap menampilkan nama bahan yang dihapus lunak dari snapshot pesanan.
     */
    public function test_invoice_detail_resolves_soft_deleted_material_from_the_order(): void
    {
        $fixture = $this->catalogFixture($this->branchOne);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'material_id' => $fixture['material']->id,
                        'quantity' => 1,
                        'width' => 2,
                        'length' => 1,
                        'is_manual_price' => false,
                    ],
                ],
            ])
            ->assertCreated();

        $fixture['material']->delete();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/invoices/'.$created->json('invoice.id'))
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.material_id', $fixture['material']->id)
            ->assertJsonPath('invoice.orders.0.material', 'Flexy 340gr');
    }

    /**
     * Memastikan peran tidak dikenal ditolak saat membaca daftar dan detail invoice.
     */
    public function test_unknown_role_cannot_read_invoices(): void
    {
        $unknown = User::factory()->create([
            'role' => 'legacy_setting',
            'branch_id' => $this->branchOne->id,
        ]);

        $this->actingAs($unknown)->getJson('/api/v1/invoices')->assertForbidden();
        $this->actingAs($unknown)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertForbidden();
    }

    /**
     * Memastikan akses detail invoice lintas cabang ditolak.
     */
    public function test_cross_branch_invoice_detail_is_forbidden(): void
    {
        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}")
            ->assertForbidden();
    }

    /**
     * Memastikan akses tiket lintas cabang oleh produksi ditolak.
     */
    public function test_production_cross_branch_ticket_is_forbidden(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/ticket")
            ->assertForbidden();
    }

    /**
     * Memastikan detail invoice untuk produksi menghapus seluruh atribut uang dan pembayaran.
     */
    public function test_production_invoice_detail_redacts_every_money_field(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.file_name', 'banner.pdf')
            ->assertJsonMissingPath('invoice.grand_total')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.discount')
            ->assertJsonMissingPath('invoice.dp_amount')
            ->assertJsonMissingPath('invoice.accounts_receivable')
            ->assertJsonMissingPath('invoice.payments')
            ->assertJsonMissingPath('invoice.orders.0.unit_price')
            ->assertJsonMissingPath('invoice.orders.0.subtotal')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');
    }

    /**
     * Memastikan produksi tidak diperbolehkan membuka nota pembayaran.
     */
    public function test_production_cannot_open_receipt(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/receipt")
            ->assertForbidden();
    }

    /**
     * Memastikan nota pembayaran dapat dibuka owner, manajer, kasir, dan desainer di cabang yang sama.
     */
    public function test_receipt_is_allowed_for_owner_manager_cashier_and_designer_in_branch(): void
    {
        foreach ([$this->owner, $this->manager, $this->cashier, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/receipt")
                ->assertOk()
                ->assertJsonPath('invoice.id', $this->invoiceOne->id);
        }

        foreach ([$this->manager, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/receipt")
                ->assertForbidden();
        }
    }

    /**
     * Memastikan produksi dapat membuka tiket kerja tanpa satu pun field finansial.
     */
    public function test_production_ticket_is_allowed_and_has_no_money(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/ticket")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceOne->id)
            ->assertJsonMissingPath('invoice.grand_total')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.discount')
            ->assertJsonMissingPath('invoice.dp_amount')
            ->assertJsonMissingPath('invoice.accounts_receivable')
            ->assertJsonMissingPath('invoice.payments')
            ->assertJsonMissingPath('invoice.orders.0.unit_price')
            ->assertJsonMissingPath('invoice.orders.0.subtotal')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');
    }

    /**
     * Memastikan akses tiket dan nota lintas cabang ditolak.
     */
    public function test_cross_branch_ticket_and_receipt_are_forbidden(): void
    {
        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/ticket")
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/receipt")
            ->assertForbidden();
    }

    /**
     * Memastikan owner dapat membaca detail invoice dari cabang mana pun.
     */
    public function test_owner_can_read_any_branch_invoice(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceTwo->id);
    }

    /**
     * Memastikan pencarian invoice mencocokkan nomor invoice, nama pelanggan, dan telepon.
     */
    public function test_invoice_search_matches_number_customer_or_phone(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?search=INV-B2-001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceTwo->id);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?search=Jane')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?search=0812345')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Memastikan filter status pembayaran bekerja sesuai kode status invoice.
     */
    public function test_invoice_status_filter(): void
    {
        $this->invoiceOne->update(['status' => 2]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?status=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);
    }

    /**
     * Memastikan filter status pengambilan bekerja dan parameter filter tidak valid ditolak dengan 422.
     */
    public function test_invoice_pickup_filter_and_invalid_filter_validation(): void
    {
        $this->invoiceOne->update(['status' => 2, 'pickup_status' => 1]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?pickup_status=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices?status=1&pickup_status=0')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices?status=invalid')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices?status=9')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices?pickup_status=9')
            ->assertStatus(422)
            ->assertJsonValidationErrors('pickup_status');
    }

    /**
     * Memastikan endpoint opsi dan kalkulasi penawaran menegakkan peran desainer serta validasi atribut cabang.
     */
    public function test_quote_options_and_quote_enforce_designer_role_and_attribute_rules(): void
    {
        $fixture = $this->catalogFixture($this->branchOne);

        $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->assertJsonFragment(['id' => $fixture['product']->id])
            ->assertJsonFragment(['id' => $fixture['finishing']->id]);

        foreach ([$this->cashier, $this->owner, $this->manager, $this->production] as $user) {
            $this->actingAs($user)->getJson('/api/v1/quotes/options')->assertForbidden();
            $this->actingAs($user)
                ->postJson('/api/v1/quotes', [
                    'product_id' => $fixture['product']->id,
                    'material_id' => $fixture['material']->id,
                    'quantity' => 1,
                ])
                ->assertForbidden();
        }

        // Base (material 25000 + finishing 5000 + display 10000) = 40000; area 2 * 1.5 = 3; qty 2 => 240000.00.
        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $fixture['product']->id,
                'material_id' => $fixture['material']->id,
                'selected_attributes' => [$fixture['finishing']->id, $fixture['display']->id],
                'quantity' => 2,
                'width' => 2,
                'length' => 1.5,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '40000.00')
            ->assertJsonPath('quote.subtotal', '240000.00');

        $extraFinishing = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Laminasi Glossy',
            'type' => 'finishing',
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $fixture['product']->id,
                'material_id' => $fixture['material']->id,
                'selected_attributes' => [$fixture['finishing']->id, $extraFinishing->id],
                'quantity' => 1,
                'width' => 1,
                'length' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');

        $otherBranchAttribute = Attribute::create([
            'branch_id' => $this->branchTwo->id,
            'name' => 'Finishing Cabang Lain',
            'type' => 'finishing',
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $fixture['product']->id,
                'material_id' => $fixture['material']->id,
                'selected_attributes' => [$otherBranchAttribute->id],
                'quantity' => 1,
                'width' => 1,
                'length' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    /**
     * Memastikan pembuatan invoice menerapkan diskon reseller cabang dari data pelanggan serta merapikan teks opsional.
     */
    public function test_create_invoice_applies_reseller_branch_discount_and_trims_optional_text(): void
    {
        $this->branchOne->update([
            'code' => '---',
            'settings' => ['reseller_discount_percent' => 10],
        ]);

        $fixture = $this->catalogFixture($this->branchOne);

        $reseller = Customer::create([
            'name' => 'Toko Reseller',
            'phone' => '0899887766',
            'type' => 'Reseller',
        ]);

        // Base (material 25000 + finishing 5000) with 10% reseller = 27000; area 2*1 = 2; qty 1 => 54000.00.
        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $reseller->id,
                'discount' => 0,
                'source' => 'Online',
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'material_id' => $fixture['material']->id,
                        'selected_attributes' => [$fixture['finishing']->id],
                        'file_name' => '  spanduk-final.pdf  ',
                        'notes' => '   ',
                        'quantity' => 1,
                        'width' => 2,
                        'length' => 1,
                        'is_manual_price' => false,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.source', 1)
            ->assertJsonPath('invoice.grand_total', '54000.00')
            ->assertJsonPath('invoice.orders.0.file_name', 'spanduk-final.pdf')
            ->assertJsonPath('invoice.orders.0.notes', null);

        $this->assertStringStartsWith('INV-BO-', (string) $response->json('invoice.invoice_number'));
    }

    /**
     * Memastikan pembuatan invoice menolak daftar item kosong dan peran selain desainer.
     */
    public function test_create_invoice_rejects_empty_items_and_non_designer_roles(): void
    {
        foreach ([$this->cashier, $this->production, $this->manager, $this->owner] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/invoices', [
                    'customer_id' => $this->customer->id,
                    'items' => [
                        [
                            'product_id' => (string) Str::uuid(),
                            'quantity' => 1,
                        ],
                    ],
                ])
                ->assertForbidden();
        }

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    /**
     * Memastikan aksi pickup hanya boleh dilakukan oleh kasir pada cabang yang sama.
     */
    public function test_pickup_is_restricted_to_cashier_role(): void
    {
        $this->invoiceOne->update(['status' => 2, 'accounts_receivable' => 0]);

        foreach ([$this->designer, $this->production, $this->owner, $this->manager] as $user) {
            $this->actingAs($user)
                ->postJson("/api/v1/invoices/{$this->invoiceOne->id}/pickup")
                ->assertForbidden();
        }
    }

    public function test_invoice_detail_renders_order_snapshots_without_pricing_object(): void
    {
        $fixture = $this->catalogFixture($this->branchOne);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [[
                    'product_id' => $fixture['product']->id,
                    'material_id' => $fixture['material']->id,
                    'quantity' => 2,
                    'width' => 2,
                    'length' => 1,
                    'is_manual_price' => false,
                ]],
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/invoices/'.$created->json('invoice.id'))
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.product_id', $fixture['product']->id)
            ->assertJsonPath('invoice.orders.0.material', 'Flexy 340gr')
            ->assertJsonMissingPath('invoice.orders.0.pricing')
            ->assertJsonMissingPath('invoice.orders.0.pricing_id');
    }

    public function test_order_resource_renders_legacy_order_with_null_identity(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.product_id', null)
            ->assertJsonPath('invoice.orders.0.material', null)
            ->assertJsonPath('invoice.orders.0.unit_price', '50000.00')
            ->assertJsonPath('invoice.orders.0.subtotal', '100000.00');
    }

    /**
     * Membuat data katalog lengkap untuk pengujian penawaran dan pembuatan invoice.
     *
     * @return array{product: Product, material: Material, finishing: Attribute, display: Attribute}
     */
    private function catalogFixture(Branch $branch): array
    {
        $category = ProductCategory::create([
            'name' => 'Large Format '.Str::random(4),
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing', 'display'],
        ]);

        $product = Product::create([
            'name' => 'Spanduk Flexy',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'name' => 'Flexy 340gr',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        $material->branchStocks()->create([
            'branch_id' => $branch->id,
            'current_stock' => 100,
            'min_stock' => 10,
        ]);

        $product->materials()->sync([$material->id]);

        $finishing = Attribute::create([
            'branch_id' => $branch->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $display = Attribute::create([
            'branch_id' => $branch->id,
            'name' => 'X-Banner',
            'type' => 'display',
            'price' => 10000,
            'cost' => 4000,
        ]);

        return [
            'product' => $product,
            'material' => $material,
            'finishing' => $finishing,
            'display' => $display,
        ];
    }

    /**
     * Membuat entitas cabang uji.
     */
    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    /**
     * Membuat entitas pengguna uji berdasarkan peran dan cabang.
     */
    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }

    /**
     * Membuat entitas invoice uji untuk cabang tertentu.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function invoice(Branch $branch, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'designer_id' => $this->designer->id,
            'status' => 1,
            'discount' => 0,
            'dp_amount' => 50000,
            'accounts_receivable' => 50000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ], $overrides));
    }

    /**
     * Membuat satu item pesanan uji pada invoice terkait.
     */
    private function order(Invoice $invoice): void
    {
        Order::create([
            'invoice_id' => $invoice->id,
            'file_name' => 'banner.pdf',
            'notes' => 'Test item',
            'area' => 2,
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 100000,
            'cost' => 60000,
            'profit' => 40000,
            'ordered_at' => now()->toDateString(),
            'status' => 0,
            'pickup_status' => 0,
            'is_manual_price' => false,
        ]);
    }

    /**
     * Membuat catatan pembayaran uang muka uji untuk invoice terkait.
     */
    private function payment(Invoice $invoice, Branch $branch, User $cashier): void
    {
        Payment::create([
            'branch_id' => $branch->id,
            'invoice_id' => $invoice->id,
            'user_id' => $cashier->id,
            'amount' => 50000,
            'payment_method' => 'Cash',
            'type' => 'down_payment',
            'cashier_name' => $cashier->name,
        ]);
    }
}
