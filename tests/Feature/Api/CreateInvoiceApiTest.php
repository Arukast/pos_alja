<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Machine;
use App\Models\MachinePricing;
use App\Models\Material;
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

    private Machine $machine;

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
            'pricing_type' => 'area',
            'product_category_id' => $category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
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

        $this->machine = Machine::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Printer A',
            'cost' => 0,
        ]);

        MachinePricing::create([
            'branch_id' => $this->branchOne->id,
            'pricing_id' => $this->pricing->id,
            'machine_id' => $this->machine->id,
            'price' => 5000,
            'cost' => 3000,
            'profit' => 2000,
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
            ->assertJsonPath('invoice.grand_total', '660000.00')
            ->assertJsonPath('invoice.orders.0.unit_price', '55000.00')
            ->assertJsonPath('invoice.orders.0.subtotal', '660000.00')
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
            'unit_price' => 55000,
            'subtotal' => 660000,
            'is_manual_price' => false,
        ]);
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

    public function test_cross_branch_machine_is_rejected(): void
    {
        $otherMachine = Machine::create([
            'branch_id' => $this->branchTwo->id,
            'name' => 'Printer B',
            'cost' => 0,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['machine_id' => $otherMachine->id])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.machine_id');
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
            'machine_id' => $this->machine->id,
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
