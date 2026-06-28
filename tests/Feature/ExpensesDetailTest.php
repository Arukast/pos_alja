<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboards\Owner\ExpensesDetail;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensesDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Branch $branch1;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch1 = Branch::create([
            'name' => 'Branch One',
        ]);

        $this->branch2 = Branch::create([
            'name' => 'Branch Two',
        ]);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'username' => 'testowner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_expenses_detail_scopes_by_branch_properly(): void
    {
        $this->actingAs($this->owner);

        // Branch 1 Expenses
        $exp1 = Expense::create([
            'branch_id' => $this->branch1->id,
            'amount' => 15000,
            'incurred_at' => now()->toDateString(),
            'type' => 'laba',
            'category' => 'Office',
            'description' => 'Notebooks',
            'cashier_name' => 'Cashier One',
        ]);

        $exp2 = Expense::create([
            'branch_id' => $this->branch1->id,
            'amount' => 25000,
            'incurred_at' => now()->toDateString(),
            'type' => 'laba',
            'category' => 'Supplies',
            'description' => 'Toner Paper',
            'cashier_name' => 'Cashier One',
        ]);

        // Branch 2 Expense
        $exp3 = Expense::create([
            'branch_id' => $this->branch2->id,
            'amount' => 50000,
            'incurred_at' => now()->toDateString(),
            'type' => 'laba',
            'category' => 'Utilities',
            'description' => 'Electricity Bills',
            'cashier_name' => 'Cashier Two',
        ]);

        // Test Branch 1 Isolation
        Livewire::test(ExpensesDetail::class, [
            'selectedBranchId' => $this->branch1->id,
            'period' => 'Daily',
        ])
            ->assertSet('selectedBranchId', $this->branch1->id)
            ->assertViewHas('expenses_total', 40000)
            ->assertViewHas('by_category', function ($byCategory) {
                return ($byCategory['Office'] ?? 0) === 15000 && ($byCategory['Supplies'] ?? 0) === 25000;
            })
            ->assertViewHas('expenses', function ($expenses) use ($exp1, $exp2) {
                return $expenses->count() === 2 && $expenses->contains('id', $exp1->id) && $expenses->contains('id', $exp2->id);
            });

        // Test Global Overview
        Livewire::test(ExpensesDetail::class, [
            'selectedBranchId' => 'all',
            'period' => 'Daily',
        ])
            ->assertSet('selectedBranchId', 'all')
            ->assertViewHas('expenses_total', 90000)
            ->assertViewHas('by_category', function ($byCategory) {
                return ($byCategory['Office'] ?? 0) === 15000
                    && ($byCategory['Supplies'] ?? 0) === 25000
                    && ($byCategory['Utilities'] ?? 0) === 50000;
            });
    }

    public function test_expenses_detail_date_filters_validation(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(ExpensesDetail::class, [
            'selectedBranchId' => 'all',
            'period' => 'Daily',
        ])
            ->set('form.startDate', '2026-06-30')
            ->set('form.endDate', '2026-06-01') // Invalid end date
            ->call('applyCustomRange')
            ->assertHasErrors(['form.endDate' => 'after_or_equal']);
    }
}
