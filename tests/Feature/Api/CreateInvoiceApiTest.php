<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $designer;

    private User $cashier;

    private User $owner;

    private Customer $customer;

    private Product $product;

    private Material $material;

    private Pricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->designer = $this->user('designer', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->owner = $this->user('owner', null);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->product = Product::create([
            'name' => 'Vinyl Banner',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 100,
            'min_stock' => 1,
        ]);

        $this->pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);
    }

    public function test_designer_creates_invoice_with_server_recomputed_prices(): void
    {
        $item = $this->item([
            'unit_price' => 1,
            'cost' => 1,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.grand_total', '600000.00')
            ->assertJsonPath('invoice.orders.0.unit_price', '50000.00')
            ->assertJsonPath('invoice.orders.0.subtotal', '600000.00')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');

        $this->assertStringStartsWith(
            'INV-'.strtoupper((string) $this->branchOne->code).'-',
            $response->json('invoice.invoice_number')
        );

        $this->assertDatabaseHas('orders', [
            'invoice_id' => $response->json('invoice.id'),
            'unit_price' => 50000,
            'subtotal' => 600000,
            'is_manual_price' => false,
        ]);
    }

    public function test_invoice_creation_audits_the_invoice_and_each_order(): void
    {
        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['discount' => 50000]))
            ->assertCreated();

        $invoiceId = (string) $response->json('invoice.id');
        $orderId = (string) $response->json('invoice.orders.0.id');

        $invoiceLog = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Invoice::class)
            ->where('model_id', $invoiceId)
            ->firstOrFail();

        $this->assertSame('50000.00', $invoiceLog->payload['discount']);
        $this->assertSame([$orderId], $invoiceLog->payload['order_ids']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'branch_id' => $this->branchOne->id,
            'user_id' => $this->designer->id,
            'model_type' => Order::class,
            'model_id' => $orderId,
        ]);

        $orderLog = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Order::class)
            ->where('model_id', $orderId)
            ->firstOrFail();

        $this->assertSame($invoiceId, $orderLog->payload['invoice_id']);
        $this->assertSame('banner.pdf', $orderLog->payload['file_name']);
    }

    public function test_manual_price_requires_values_and_is_audit_logged(): void
    {
        $item = $this->item([
            'is_manual_price' => true,
            'unit_price' => 60000,
            'cost' => 25000,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.subtotal', '720000.00')
            ->assertJsonPath('invoice.orders.0.is_manual_price', true);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'MANUAL_PRICE',
            'model_id' => $response->json('invoice.orders.0.id'),
        ]);

        $invalid = $this->item([
            'is_manual_price' => true,
            'unit_price' => 60000,
            'cost' => null,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$invalid]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_m2_item_with_missing_geometry_returns_422(): void
    {
        $item = $this->item(['width' => null, 'length' => null]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_manual_price_uses_area_factor_only_for_m2(): void
    {
        $item = $this->item([
            'is_manual_price' => true,
            'unit_price' => 60000,
            'cost' => 25000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.subtotal', '720000.00'); // 60000 * 2 * area 6

        $this->product->update(['selling_unit' => 'pcs']);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.subtotal', '120000.00'); // 60000 * 2 * factor 1
    }

    public function test_pricing_failure_maps_to_422_instead_of_500(): void
    {
        $otherMaterial = Material::create([
            'name' => 'Tiered Only',
            'product_category_id' => $this->material->product_category_id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $otherMaterial->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 100,
            'min_stock' => 1,
        ]);

        $pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $otherMaterial->id,
            'customer_type' => 'Reguler',
            'min_qty' => 100,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['pricing_id' => $pricing->id, 'quantity' => 2])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_manual_cost_cannot_exceed_price(): void
    {
        $item = $this->item([
            'is_manual_price' => true,
            'unit_price' => 100,
            'cost' => 200,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_cashier_and_owner_cannot_create_invoices(): void
    {
        foreach ([$this->cashier, $this->owner] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/invoices', $this->payload())
                ->assertForbidden();
        }
    }

    public function test_designer_updates_items_on_an_unpaid_dp_invoice(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 100000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'deadline' => now()->addDays(5)->toDateString(),
                'items' => [$this->item([
                    'id' => $orderId,
                    'quantity' => 3,
                    'file_name' => 'banner-revisi.pdf',
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.status', 1)
            ->assertJsonPath('invoice.grand_total', '900000.00')
            ->assertJsonPath('invoice.dp_amount', '100000.00')
            ->assertJsonPath('invoice.accounts_receivable', '800000.00')
            ->assertJsonPath('invoice.orders.0.id', $orderId)
            ->assertJsonPath('invoice.orders.0.file_name', 'banner-revisi.pdf');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'quantity' => 3]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'UPDATE',
            'model_id' => $invoiceId,
        ]);
    }

    public function test_invoice_creation_rejects_a_pricing_with_a_missing_material_relation(): void
    {
        $this->material->delete();

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_creation_persists_width_length_and_selected_attributes(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item([
                    'width' => 2.5,
                    'length' => 4,
                    'selected_attributes' => [$attribute->id],
                ])],
            ]))
            ->assertCreated();

        $order = Order::query()->where('invoice_id', $response->json('invoice.id'))->firstOrFail();

        $this->assertEquals(2.5, (float) $order->width);
        $this->assertEquals(4, (float) $order->length);
        $this->assertSame([$attribute->id], $order->selected_attributes);
    }

    public function test_invoice_item_update_persists_width_length_and_selected_attributes(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item([
                    'id' => $orderId,
                    'width' => 1.5,
                    'length' => 2.5,
                    'selected_attributes' => [$attribute->id],
                ])],
            ])
            ->assertOk();

        $order = Order::query()->findOrFail($orderId);

        $this->assertEquals(1.5, (float) $order->width);
        $this->assertEquals(2.5, (float) $order->length);
        $this->assertSame([$attribute->id], $order->selected_attributes);
    }

    public function test_invoice_creation_rejects_an_inactive_attribute(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Finishing',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.selected_attributes');

        $errorBag = $response->json('errors');

        $this->assertStringContainsString(
            'atribut',
            strtolower((string) ($errorBag['items.0.selected_attributes'][0] ?? ''))
        );
    }

    public function test_invoice_creation_rejects_an_inactive_type(): void
    {
        $type = AttributeType::create([
            'name' => 'Stempel',
            'slug' => 'stempel',
            'sort_order' => 4,
            'is_active' => false,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Stempel Karet',
            'type' => $type->slug,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.selected_attributes');
    }

    public function test_invoice_creation_accepts_a_custom_type_attribute(): void
    {
        $type = AttributeType::create([
            'name' => 'Mata Ayam',
            'slug' => 'mata-ayam',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam 12mm',
            'type' => $type->slug,
            'price' => 5000,
            'cost' => 3000,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.unit_price', '55000.00')
            ->assertJsonPath('invoice.grand_total', '660000.00')
            ->assertJsonPath('invoice.orders.0.selected_attributes.0', $attribute->id);

        $order = Order::query()->where('invoice_id', $response->json('invoice.id'))->firstOrFail();

        $this->assertSame([$attribute->id], $order->selected_attributes);
    }

    public function test_invoice_edit_keeps_an_inactive_attribute_already_on_the_invoice(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertCreated();

        $attribute->update(['is_active' => false]);
        AttributeType::query()->where('slug', 'finishing')->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->item([
                    'id' => $created->json('invoice.orders.0.id'),
                    'selected_attributes' => [$attribute->id],
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.selected_attributes.0', $attribute->id);
    }

    public function test_invoice_edit_keeps_an_attribute_of_an_inactive_type_already_on_the_invoice(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertCreated();

        AttributeType::query()->where('slug', 'finishing')->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->item([
                    'id' => $created->json('invoice.orders.0.id'),
                    'selected_attributes' => [$attribute->id],
                ])],
            ])
            ->assertOk();
    }

    public function test_invoice_edit_rejects_a_different_inactive_attribute(): void
    {
        $onInvoice = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$onInvoice->id]])],
            ]))
            ->assertCreated();

        $other = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Laminasi',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->item([
                    'id' => $created->json('invoice.orders.0.id'),
                    'selected_attributes' => [$other->id],
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.selected_attributes');
    }

    public function test_invoice_edit_allowance_is_invoice_wide_across_items(): void
    {
        $finishing = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);
        $display = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'X-Banner',
            'type' => 'display',
        ]);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [
                    $this->item(['selected_attributes' => [$finishing->id]]),
                    $this->item(['selected_attributes' => [$display->id]]),
                ],
            ]))
            ->assertCreated();

        $finishing->update(['is_active' => false]);
        $display->update(['is_active' => false]);

        $orderIds = collect($created->json('invoice.orders'))->pluck('id')->all();

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [
                    $this->item(['id' => $orderIds[0], 'selected_attributes' => [$finishing->id]]),
                    $this->item(['id' => $orderIds[1], 'selected_attributes' => [$display->id]]),
                ],
            ])
            ->assertOk();
    }

    public function test_invoice_edit_ignores_a_client_supplied_allowance(): void
    {
        $onInvoice = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$onInvoice->id]])],
            ]))
            ->assertCreated();

        $other = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Laminasi',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'inactive_attribute_allowance' => [$other->id],
                'allow_inactive_attributes' => true,
                'items' => [$this->item([
                    'id' => $created->json('invoice.orders.0.id'),
                    'selected_attributes' => [$other->id],
                    'inactive_attribute_allowance' => [$other->id],
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.selected_attributes');
    }

    public function test_invoice_edit_allows_more_than_three_attributes(): void
    {
        $customType = AttributeType::create([
            'name' => 'Mata Ayam',
            'slug' => 'mata-ayam',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $attributes = [
            Attribute::create([
                'branch_id' => $this->branchOne->id,
                'name' => 'Glossy',
                'type' => 'finishing',
            ]),
            Attribute::create([
                'branch_id' => $this->branchOne->id,
                'name' => 'Potong Lurus',
                'type' => 'cut',
            ]),
            Attribute::create([
                'branch_id' => $this->branchOne->id,
                'name' => 'X-Banner',
                'type' => 'display',
            ]),
            Attribute::create([
                'branch_id' => $this->branchOne->id,
                'name' => 'Mata Ayam 12mm',
                'type' => $customType->slug,
            ]),
        ];
        $ids = array_map(static fn (Attribute $attribute): string => $attribute->id, $attributes);

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => $ids])],
            ]))
            ->assertCreated()
            ->assertJsonCount(4, 'invoice.orders.0.selected_attributes');

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->item([
                    'id' => $created->json('invoice.orders.0.id'),
                    'selected_attributes' => $ids,
                ])],
            ])
            ->assertOk()
            ->assertJsonCount(4, 'invoice.orders.0.selected_attributes');
    }

    public function test_invoice_item_update_preserves_an_omitted_deadline_and_clears_an_explicit_null(): void
    {
        $deadline = now()->addDays(5)->toDateString();

        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['deadline' => $deadline]))
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $orderId])],
            ])
            ->assertOk();

        $this->assertSame($deadline, Invoice::query()->findOrFail($invoiceId)->deadline);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'deadline' => null,
                'items' => [$this->item(['id' => $orderId])],
            ])
            ->assertOk();

        $this->assertNull(Invoice::query()->findOrFail($invoiceId)->deadline);
    }

    public function test_invoice_item_update_rejects_manual_price_orders(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item([
                    'is_manual_price' => true,
                    'unit_price' => 60000,
                    'cost' => 25000,
                ])],
            ]))
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $orderId])],
            ])
            ->assertStatus(409);

        $order = Order::query()->findOrFail($orderId);

        $this->assertTrue((bool) $order->is_manual_price);
        $this->assertEquals(60000, (float) $order->unit_price);
    }

    /** A designer can append a line and later remove it while keeping the invoice payment history. */
    public function test_designer_can_add_and_remove_invoice_items(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $existingOrderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 100000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $existingOrderId]), $this->item(['quantity' => 1])],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'invoice.orders')
            ->assertJsonPath('invoice.grand_total', '900000.00')
            ->assertJsonPath('invoice.accounts_receivable', '800000.00');

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $existingOrderId, 'quantity' => 1])],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'invoice.orders')
            ->assertJsonPath('invoice.grand_total', '300000.00')
            ->assertJsonPath('invoice.accounts_receivable', '200000.00');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('orders', 1);
    }

    /** Item updates require a designer in the invoice branch and return 401 to guests. */
    public function test_invoice_item_update_enforces_auth_and_branch_scope(): void
    {
        $this->putJson('/api/v1/invoices/'.Str::uuid().'/items', [])->assertUnauthorized();

        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $payload = ['items' => [$this->item()]];

        $this->actingAs($this->cashier)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertForbidden();

        $otherDesigner = $this->user('designer', $this->branchTwo);
        $this->actingAs($otherDesigner)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertForbidden();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    /** Fully paid invoices and invoices with an order already in production return 409. */
    public function test_invoice_item_update_is_blocked_after_payment_or_production(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');
        $payload = ['items' => [$this->item()]];

        Invoice::query()->whereKey($invoiceId)->update(['status' => 2]);
        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertStatus(409);

        Invoice::query()->whereKey($invoiceId)->update(['status' => 1]);
        Order::query()->whereKey($orderId)->update(['status' => 1]);
        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertStatus(409);
    }

    /** New totals below previously collected payments are rejected without losing payment history. */
    public function test_invoice_item_update_cannot_make_receivable_negative(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 200000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['width' => 0.5, 'length' => 1, 'quantity' => 1])],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals(400000, Invoice::query()->findOrFail($invoiceId)->accounts_receivable);
    }

    public function test_cross_branch_pricing_is_rejected(): void
    {
        $otherPricing = Pricing::create([
            'branch_id' => $this->branchTwo->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $item = $this->item(['pricing_id' => $otherPricing->id]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['discount' => 700000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount');
    }

    public function test_invoice_creation_rolls_back_when_a_later_item_fails(): void
    {
        $otherPricing = Pricing::create([
            'branch_id' => $this->branchTwo->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [
                    $this->item(),
                    $this->item(['pricing_id' => $otherPricing->id]),
                ],
            ]))
            ->assertStatus(422);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_designer_can_create_a_customer_and_cashier_cannot(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/customers', [
                'name' => 'New Customer',
                'phone' => '0812000000',
                'institution' => 'Masyarakat/Retail',
                'type' => 'Reguler',
            ])
            ->assertCreated()
            ->assertJsonPath('customer.name', 'New Customer');

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/customers', [
                'name' => 'Blocked Customer',
            ])
            ->assertForbidden();
    }

    public function test_invoice_detail_exposes_product_composition_for_edit(): void
    {
        $type = AttributeType::query()->where('slug', 'finishing')->firstOrFail();

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => $type->slug,
        ]);

        $this->product->attributeTypes()->sync([$type->id]);
        $this->product->compatibleAttributes()->sync([$attribute->id]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ]))
            ->assertCreated();

        $invoiceId = (string) $response->json('invoice.id');

        $this->actingAs($this->designer)
            ->getJson("/api/v1/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.pricing.product_id', $this->product->id)
            ->assertJsonPath('invoice.orders.0.pricing.requires_material', true)
            ->assertJsonPath('invoice.orders.0.pricing.uses_attributes', true)
            ->assertJsonPath('invoice.orders.0.pricing.attribute_type_ids.0', $type->id)
            ->assertJsonPath('invoice.orders.0.pricing.attribute_ids.0', $attribute->id);
    }

    public function test_invoice_detail_exposes_disabled_composition_flags_with_pivot_ids(): void
    {
        $type = AttributeType::query()->where('slug', 'finishing')->firstOrFail();

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => $type->slug,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();

        $invoiceId = (string) $response->json('invoice.id');

        // T5 rejects attributes on new lines once the flags are off; flip them after creation.
        $this->product->update([
            'requires_material' => false,
            'uses_attributes' => false,
        ]);
        $this->product->attributeTypes()->sync([$type->id]);
        $this->product->compatibleAttributes()->sync([$attribute->id]);

        $this->actingAs($this->designer)
            ->getJson("/api/v1/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.pricing.product_id', $this->product->id)
            ->assertJsonPath('invoice.orders.0.pricing.requires_material', false)
            ->assertJsonPath('invoice.orders.0.pricing.uses_attributes', false)
            ->assertJsonPath('invoice.orders.0.pricing.attribute_type_ids.0', $type->id)
            ->assertJsonPath('invoice.orders.0.pricing.attribute_ids.0', $attribute->id);
    }

    public function test_invoice_edit_grandfathers_a_whole_unchanged_line_after_product_change(): void
    {
        $attribute = $this->makeAttribute('Glossy', 'finishing');
        $invoice = $this->invoiceWithAttributes([$attribute->id]);
        $this->makeProductIncompatible();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'selected_attributes' => [$attribute->id],
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.selected_attributes.0', $attribute->id);
    }

    public function test_invoice_edit_rejects_changed_pricing_line_after_product_change(): void
    {
        $attribute = $this->makeAttribute('Glossy', 'finishing');
        $invoice = $this->invoiceWithAttributes([$attribute->id]);
        $this->makeProductIncompatible();

        $secondPricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 60000,
            'cost' => 20000,
            'profit' => 40000,
        ]);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'pricing_id' => $secondPricing->id,
                    'selected_attributes' => [$attribute->id],
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_edit_rejects_changed_selection_after_product_change(): void
    {
        $glossy = $this->makeAttribute('Glossy', 'finishing');
        $matte = $this->makeAttribute('Matte', 'finishing');
        $invoice = $this->invoiceWithAttributes([$glossy->id]);
        $this->makeProductIncompatible(keepMaterial: true);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'selected_attributes' => [$matte->id],
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.selected_attributes');
    }

    public function test_invoice_edit_rejects_a_new_incompatible_line(): void
    {
        $attribute = $this->makeAttribute('Glossy', 'finishing');
        $invoice = $this->invoiceWithAttributes([$attribute->id]);
        $this->makeProductIncompatible();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item(['selected_attributes' => [$attribute->id]])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_edit_grandfathering_ignores_attribute_order_and_client_flags(): void
    {
        $finishing = $this->makeAttribute('Glossy', 'finishing');
        $display = $this->makeAttribute('X-Banner', 'display');
        $invoice = $this->invoiceWithAttributes([$finishing->id, $display->id]);
        $this->makeProductIncompatible();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'inactive_attribute_allowance' => [$finishing->id, $display->id],
                'allow_product_capability' => true,
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'selected_attributes' => [$display->id, $finishing->id],
                    'inactive_attribute_allowance' => [$finishing->id, $display->id],
                    'allow_product_capability' => true,
                ])],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'invoice.orders.0.selected_attributes');
    }

    public function test_invoice_edit_client_skip_flag_cannot_grandfather_a_changed_line(): void
    {
        $attribute = $this->makeAttribute('Glossy', 'finishing');
        $invoice = $this->invoiceWithAttributes([$attribute->id]);
        $this->makeProductIncompatible();

        $secondPricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 60000,
            'cost' => 20000,
            'profit' => 40000,
        ]);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'skip_product_capability_checks' => true,
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'pricing_id' => $secondPricing->id,
                    'selected_attributes' => [$attribute->id],
                    'skip_product_capability_checks' => true,
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    private function makeAttribute(string $name, string $type): Attribute
    {
        return Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    /**
     * @param  list<string>  $attributeIds
     * @return array{id: string, order_id: string}
     */
    private function invoiceWithAttributes(array $attributeIds): array
    {
        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['selected_attributes' => $attributeIds])],
            ]))
            ->assertCreated();

        return [
            'id' => (string) $response->json('invoice.id'),
            'order_id' => (string) $response->json('invoice.orders.0.id'),
        ];
    }

    private function makeProductIncompatible(bool $keepMaterial = false): void
    {
        $this->product->update([
            'requires_material' => $keepMaterial,
            'uses_attributes' => false,
        ]);

        if (! $keepMaterial) {
            $this->product->materials()->sync([]);
        }

        $this->product->attributeTypes()->sync([]);
        $this->product->compatibleAttributes()->sync([]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'discount' => 0,
            'deadline' => now()->addDay()->toDateString(),
            'source' => 'Offline',
            'items' => [$this->item()],
        ], $overrides);
    }

    private function item(array $overrides = []): array
    {
        return array_merge([
            'pricing_id' => $this->pricing->id,
            'file_name' => 'banner.pdf',
            'notes' => 'Test item',
            'quantity' => 2,
            'width' => 2,
            'length' => 3,
            // Legacy clients may still send machine_id; it must be ignored.
            'machine_id' => (string) Str::uuid(),
            'is_manual_price' => false,
        ], $overrides);
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
