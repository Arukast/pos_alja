<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboards\Owner\BaseCostDetail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BaseCostDetailTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch1;

    protected Branch $branch2;

    protected Customer $customer;

    protected Material $material1;

    protected Material $material2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch1 = Branch::create(['name' => 'Branch One']);
        $this->branch2 = Branch::create(['name' => 'Branch Two']);

        $this->customer = Customer::create([
            'name' => 'John Customer',
            'type' => 'Retail',
        ]);

        $this->material1 = Material::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Material 1',
            'category' => 'Large Format',
            'type' => 'Banner',
            'dimensions' => '1x1',
            'cost' => 10000,
            'price' => 15000,
            'current_stock' => 100,
            'min_stock' => 10,
        ]);

        $this->material2 = Material::create([
            'branch_id' => $this->branch2->id,
            'name' => 'Material 2',
            'category' => 'Print Kertas',
            'type' => 'A4',
            'dimensions' => 'A4',
            'cost' => 500,
            'price' => 1000,
            'current_stock' => 1000,
            'min_stock' => 50,
        ]);
    }

    public function test_base_cost_detail_scopes_by_branch_properly(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null,
        ]);

        $invoice1 = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'branch_id' => $this->branch1->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $invoice2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0002',
            'branch_id' => $this->branch2->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $waste1 = WastedMaterial::create([
            'branch_id' => $this->branch1->id,
            'logged_at' => now(),
            'material_id' => $this->material1->id,
            'quantity' => 2.0,
            'notes' => 'Test waste 1',
        ]);

        $waste2 = WastedMaterial::create([
            'branch_id' => $this->branch2->id,
            'logged_at' => now(),
            'material_id' => $this->material2->id,
            'quantity' => 10.0,
            'notes' => 'Test waste 2',
        ]);

        // Branch 1 details only
        Livewire::actingAs($owner)
            ->test(BaseCostDetail::class, [
                'selectedBranchId' => $this->branch1->id,
                'period' => 'Daily',
            ])
            ->assertViewHas('total_hpp', 30000 + (2 * 10000)) // invoice cost + waste cost
            ->assertViewHas('invoice_cost_total', 30000)
            ->assertViewHas('wasted_cost_total', 20000)
            ->assertViewHas('invoices', function ($invoices) use ($invoice1, $invoice2) {
                return $invoices->contains($invoice1) && ! $invoices->contains($invoice2);
            })
            ->assertViewHas('wasted_materials', function ($wastes) use ($waste1, $waste2) {
                return $wastes->contains(fn ($w) => $w->id === $waste1->id) && ! $wastes->contains(fn ($w) => $w->id === $waste2->id);
            });

        // Global "all" details
        Livewire::actingAs($owner)
            ->test(BaseCostDetail::class, [
                'selectedBranchId' => 'all',
                'period' => 'Daily',
            ])
            ->assertViewHas('total_hpp', 30000 + 60000 + (2 * 10000) + (10 * 500))
            ->assertViewHas('invoice_cost_total', 90000)
            ->assertViewHas('wasted_cost_total', 25000)
            ->assertViewHas('invoices', function ($invoices) use ($invoice1, $invoice2) {
                return $invoices->contains($invoice1) && $invoices->contains($invoice2);
            })
            ->assertViewHas('wasted_materials', function ($wastes) use ($waste1, $waste2) {
                return $wastes->contains(fn ($w) => $w->id === $waste1->id) && $wastes->contains(fn ($w) => $w->id === $waste2->id);
            });
    }

    public function test_base_cost_detail_date_filters_validation(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null,
        ]);

        Livewire::actingAs($owner)
            ->test(BaseCostDetail::class, [
                'selectedBranchId' => 'all',
                'period' => 'Daily',
            ])
            ->set('form.startDate', '2026-06-25')
            ->set('form.endDate', '2026-06-20') // Invalid: end date before start date
            ->call('applyCustomRange')
            ->assertHasErrors(['form.endDate']);
    }
}
