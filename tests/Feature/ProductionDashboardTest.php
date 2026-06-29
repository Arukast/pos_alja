<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Livewire\Dashboards\Production;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected User $productionUser;

    protected User $designerUser;

    protected Customer $customer;

    protected Material $material;

    protected Product $areaProduct;

    protected Product $unitProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Main Branch',
        ]);

        $this->productionUser = User::factory()->create([
            'role' => 'production',
            'branch_id' => $this->branch->id,
        ]);

        $this->designerUser = User::factory()->create([
            'role' => 'designer',
            'branch_id' => $this->branch->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'type' => 'Reguler',
        ]);

        $this->material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Flexy 340gr',
            'category' => 'meter',
            'type' => 'outdoor',
        ]);

        MaterialBranchStock::create([
            'id' => (string) Str::uuid(),
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
        ]);

        $this->areaProduct = Product::create([
            'name' => 'Banner Outdoor',
            'pricing_type' => 'area',
        ]);

        $this->unitProduct = Product::create([
            'name' => 'Sticker Pack',
            'pricing_type' => 'unit',
        ]);
    }

    public function test_production_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->productionUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Production::class);
    }

    public function test_production_user_can_start_order_production_with_area_deduction(): void
    {
        Event::fake();

        // Create a pricing tier for area-based product
        $pricingId = (string) Str::uuid();
        DB::table('pricing')->insert([
            'id' => $pricingId,
            'branch_id' => $this->branch->id,
            'product_id' => $this->areaProduct->id,
            'material_id' => $this->material->id,
            'price' => 25000,
            'cost' => 15000,
            'profit' => 10000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0001',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toIso8601String(),
            'customer_id' => $this->customer->id,
            'designer_id' => $this->designerUser->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 50000,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
        ]);

        $order = Order::create([
            'invoice_id' => $invoice->id,
            'pricing_id' => $pricingId,
            'file_name' => 'banner_design.pdf',
            'area' => 2.00, // 2 square meters
            'quantity' => 2.00, // qty 2
            'unit_price' => 25000,
            'subtotal' => 50000,
            'cost' => 30000,
            'profit' => 20000,
            'ordered_at' => now()->toIso8601String(),
            'status' => 0, // Pending
        ]);

        Livewire::actingAs($this->productionUser)
            ->test(Production::class)
            ->call('startOrderProduction', $order->id)
            ->assertHasNoErrors();

        // Status must be 1 (Printing) and operator assigned
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 1,
            'operator_id' => $this->productionUser->id,
        ]);

        // History log created
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => 1,
            'user_id' => $this->productionUser->id,
            'notes' => 'Production started',
        ]);

        // Stock deduction check: area-based, so quantity * area = 2 * 2 = 4 units deducted
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 96.00,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    public function test_production_user_can_start_order_production_with_unit_deduction(): void
    {
        Event::fake();

        // Create a pricing tier for unit-based product
        $pricingId = (string) Str::uuid();
        DB::table('pricing')->insert([
            'id' => $pricingId,
            'branch_id' => $this->branch->id,
            'product_id' => $this->unitProduct->id,
            'material_id' => $this->material->id,
            'price' => 10000,
            'cost' => 5000,
            'profit' => 5000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0002',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toIso8601String(),
            'customer_id' => $this->customer->id,
            'designer_id' => $this->designerUser->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 20000,
            'grand_total' => 20000,
            'total_cost' => 10000,
            'total_profit' => 10000,
        ]);

        $order = Order::create([
            'invoice_id' => $invoice->id,
            'pricing_id' => $pricingId,
            'file_name' => 'stickers.pdf',
            'area' => 0.00,
            'quantity' => 2.00, // qty 2
            'unit_price' => 10000,
            'subtotal' => 20000,
            'cost' => 10000,
            'profit' => 10000,
            'ordered_at' => now()->toIso8601String(),
            'status' => 0, // Pending
        ]);

        Livewire::actingAs($this->productionUser)
            ->test(Production::class)
            ->call('startOrderProduction', $order->id)
            ->assertHasNoErrors();

        // Stock deduction check: unit-based, so quantity = 2 units deducted
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 98.00,
        ]);
    }

    public function test_production_user_can_complete_order_production(): void
    {
        Event::fake();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0003',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toIso8601String(),
            'customer_id' => $this->customer->id,
            'designer_id' => $this->designerUser->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 20000,
            'grand_total' => 20000,
            'total_cost' => 10000,
            'total_profit' => 10000,
        ]);

        $order = Order::create([
            'invoice_id' => $invoice->id,
            'file_name' => 'flyer.pdf',
            'area' => 0.00,
            'quantity' => 1.00,
            'unit_price' => 20000,
            'subtotal' => 20000,
            'cost' => 10000,
            'profit' => 10000,
            'ordered_at' => now()->toIso8601String(),
            'status' => 1, // Printing
        ]);

        Livewire::actingAs($this->productionUser)
            ->test(Production::class)
            ->call('completeOrderProduction', $order->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 2, // Completed
            'operator_id' => $this->productionUser->id,
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => 2,
            'user_id' => $this->productionUser->id,
            'notes' => 'Production completed',
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    public function test_production_user_can_log_waste(): void
    {
        $machine = Machine::create([
            'branch_id' => $this->branch->id,
            'name' => 'Roland Soljet',
        ]);

        Livewire::actingAs($this->productionUser)
            ->test(Production::class)
            ->set('form.materialId', $this->material->id)
            ->set('form.machineId', $machine->id)
            ->set('form.quantity', '3.50')
            ->set('form.dimensions', '1.5x2')
            ->set('form.designerId', $this->designerUser->id)
            ->set('form.notes', 'Calibration fail')
            ->call('logWaste')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wasted_materials', [
            'material_id' => $this->material->id,
            'machine_id' => $machine->id,
            'quantity' => 3.50,
            'dimensions' => '1.5x2',
            'designer_id' => $this->designerUser->id,
            'operator_id' => $this->productionUser->id,
            'notes' => 'Calibration fail',
        ]);

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 96.50,
        ]);
    }
}
