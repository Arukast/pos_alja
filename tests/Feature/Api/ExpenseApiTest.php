<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $production;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->production = $this->user('production', $this->branchOne);

        $this->expense($this->branchOne, 'Paper', 25000, now()->toDateString());
        $this->expense($this->branchTwo, 'Ink', 40000, now()->toDateString());
    }

    public function test_cashier_sees_only_own_branch_expenses(): void
    {
        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/expenses')
            ->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->all();

        $this->assertContains('Paper', $descriptions);
        $this->assertNotContains('Ink', $descriptions);
    }

    public function test_manager_sees_only_own_branch_expenses(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/expenses')
            ->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->all();

        $this->assertContains('Paper', $descriptions);
        $this->assertNotContains('Ink', $descriptions);
    }

    public function test_owner_sees_all_branch_expenses(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/expenses')
            ->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->all();

        $this->assertContains('Paper', $descriptions);
        $this->assertContains('Ink', $descriptions);
    }

    public function test_production_cannot_read_expenses(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/expenses')
            ->assertForbidden();
    }

    public function test_expense_date_filter_and_pagination_shape(): void
    {
        $this->expense($this->branchOne, 'Yesterday', 10000, now()->subDay()->toDateString());

        $this->actingAs($this->owner)
            ->getJson('/api/v1/expenses?from='.now()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonMissing(['description' => 'Yesterday']);
    }

    public function test_cashier_can_record_an_expense(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 12500,
                'type' => 'laba',
                'incurred_at' => now()->toDateString(),
                'description' => 'Cleaning supplies',
                'category' => 'Supplies',
            ])
            ->assertCreated()
            ->assertJsonPath('expense.amount', '12500.00')
            ->assertJsonPath('expense.cashier_name', $this->cashier->name);

        $this->assertDatabaseHas('expenses', [
            'branch_id' => $this->branchOne->id,
            'amount' => 12500,
            'description' => 'Cleaning supplies',
        ]);
    }

    public function test_cashier_expense_writes_create_audit_with_money_string(): void
    {
        $date = now()->toDateString();

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 25000,
                'type' => 'laba',
                'incurred_at' => $date,
                'description' => 'Cleaning supplies',
                'category' => 'Supplies',
            ])
            ->assertCreated();

        $expenseId = (string) $response->json('expense.id');

        $log = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Expense::class)
            ->where('model_id', $expenseId)
            ->firstOrFail();

        $this->assertSame($this->cashier->id, $log->user_id);
        $this->assertSame($this->cashier->username, $log->username);
        $this->assertSame('cashier', $log->role);
        $this->assertSame($this->branchOne->id, $log->branch_id);

        $this->assertSame('25000.00', $log->payload['amount']);
        $this->assertSame('laba', $log->payload['type']);
        $this->assertSame('Supplies', $log->payload['category']);
        $this->assertSame($date, $log->payload['incurred_at']);
        $this->assertSame('Cleaning supplies', $log->payload['description']);

        $this->assertSame(1, ActivityLog::query()
            ->where('model_type', Expense::class)
            ->where('model_id', $expenseId)
            ->count());
    }

    public function test_unauthorized_and_invalid_expense_writes_do_not_write_audit(): void
    {
        $date = now()->toDateString();

        $this->postJson('/api/v1/expenses', [
            'amount' => 10000,
            'type' => 'laba',
            'incurred_at' => $date,
        ])->assertUnauthorized();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/expenses', [
                'amount' => 10000,
                'type' => 'laba',
                'incurred_at' => $date,
            ])
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 0,
                'type' => 'laba',
                'incurred_at' => $date,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_non_cashier_cannot_record_an_expense(): void
    {
        foreach ([$this->owner, $this->manager, $this->production] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/expenses', [
                    'amount' => 10000,
                    'type' => 'laba',
                    'incurred_at' => now()->toDateString(),
                ])
                ->assertForbidden();
        }
    }

    public function test_expense_amount_must_be_positive(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 0,
                'type' => 'laba',
                'incurred_at' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
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

    private function expense(Branch $branch, string $description, int $amount, string $date): Expense
    {
        return Expense::create([
            'branch_id' => $branch->id,
            'incurred_at' => $date,
            'amount' => $amount,
            'description' => $description,
            'cashier_name' => 'Cashier Account',
            'type' => 'laba',
            'category' => 'Supplies',
        ]);
    }
}
