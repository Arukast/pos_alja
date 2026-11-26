<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private ActivityLog $createLog;

    private ActivityLog $paymentLog;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        $this->manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id]);

        $this->createLog = ActivityLog::create([
            'branch_id' => $branch->id,
            'user_id' => $this->manager->id,
            'username' => 'manager',
            'role' => 'manager',
            'action' => 'CREATE',
            'description' => 'Created an invoice',
            'model_type' => 'App\\Models\\Invoice',
            'model_id' => (string) Str::uuid(),
            'payload' => ['invoice_number' => 'INV-1'],
        ]);

        $this->paymentLog = ActivityLog::create([
            'branch_id' => $branch->id,
            'user_id' => $this->manager->id,
            'username' => 'manager',
            'role' => 'manager',
            'action' => 'UPDATE',
            'description' => 'Registered a payment',
            'model_type' => 'App\\Models\\Payment',
            'model_id' => (string) Str::uuid(),
            'payload' => ['amount' => '50000.00'],
        ]);
    }

    public function test_owner_can_list_search_and_sort_audit_logs(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(2, 'data');

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/audit-logs?search=payment')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($this->paymentLog->id, $response->json('data.0.id'));

        $this->actingAs($this->owner)
            ->getJson('/api/v1/audit-logs?sort=action&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'CREATE');
    }

    public function test_owner_can_view_audit_detail(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/v1/audit-logs/{$this->createLog->id}")
            ->assertOk()
            ->assertJsonPath('activity_log.id', $this->createLog->id)
            ->assertJsonPath('activity_log.payload.invoice_number', 'INV-1');
    }

    public function test_non_owner_cannot_read_audit_logs(): void
    {
        $this->actingAs($this->manager)
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/audit-logs/{$this->createLog->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_read_audit_logs(): void
    {
        $this->getJson('/api/v1/audit-logs')
            ->assertUnauthorized();

        $this->getJson("/api/v1/audit-logs/{$this->createLog->id}")
            ->assertUnauthorized();
    }

    public function test_unknown_audit_log_returns_not_found(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/audit-logs/'.Str::uuid())
            ->assertNotFound();
    }
}
