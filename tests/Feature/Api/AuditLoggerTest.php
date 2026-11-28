<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Material;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;

    private Branch $branchB;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA']);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB']);

        $this->actor = User::factory()->create([
            'role' => 'designer',
            'branch_id' => $this->branchA->id,
        ]);
    }

    public function test_record_writes_actor_branch_role_and_payload(): void
    {
        $material = $this->material();

        $log = app(AuditLogger::class)->record(
            $this->actor,
            'UPDATE',
            'Memperbarui bahan',
            $material,
            ['before' => 1, 'after' => 2],
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->actor->id,
            'username' => $this->actor->username,
            'role' => 'designer',
            'action' => 'UPDATE',
            'description' => 'Memperbarui bahan',
            'model_type' => Material::class,
            'model_id' => $material->id,
        ]);

        $this->assertSame(['before' => 1, 'after' => 2], $log->payload);
        $this->assertNotNull($log->created_at);
    }

    public function test_null_actor_writes_system_identity(): void
    {
        $log = app(AuditLogger::class)->record(null, 'BACKUP', 'Menjalankan backup');

        $this->assertNull($log->user_id);
        $this->assertSame('system', $log->username);
        $this->assertSame('system', $log->role);
        $this->assertNull($log->branch_id);
    }

    public function test_explicit_branch_overrides_actor_branch(): void
    {
        $log = app(AuditLogger::class)->record(
            $this->actor,
            'ADJUST',
            'Menyesuaikan stok bahan',
            null,
            [],
            $this->branchB->id,
        );

        $this->assertSame($this->branchB->id, $log->branch_id);
        $this->assertSame($this->actor->id, $log->user_id);
    }

    public function test_sensitive_keys_are_scrubbed_from_payload(): void
    {
        $log = app(AuditLogger::class)->record(
            $this->actor,
            'CREATE',
            'Membuat pengguna',
            null,
            [
                'password' => 'secret',
                'PASSWORD' => 'secret',
                'password_confirmation' => 'secret',
                'remember_token' => 'token',
                'token' => 'token',
                'access_key' => 'key',
                'secret_key' => 'key',
                'nested' => ['secret' => 'x', 'ok' => 'y'],
            ],
        );

        $payload = $log->payload;

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('PASSWORD', $payload);
        $this->assertArrayNotHasKey('password_confirmation', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
        $this->assertArrayNotHasKey('token', $payload);
        $this->assertArrayNotHasKey('access_key', $payload);
        $this->assertArrayNotHasKey('secret_key', $payload);
        $this->assertSame(['ok' => 'y'], $payload['nested']);
    }

    public function test_description_is_truncated_to_255_characters(): void
    {
        $log = app(AuditLogger::class)->record(
            $this->actor,
            'UPDATE',
            str_repeat('a', 300),
        );

        $this->assertSame(255, strlen($log->description));
    }

    public function test_subject_sets_model_type_and_model_id(): void
    {
        $material = $this->material();

        $log = app(AuditLogger::class)->record(
            $this->actor,
            'DELETE',
            'Menghapus bahan',
            $material,
        );

        $this->assertSame(Material::class, $log->model_type);
        $this->assertSame($material->id, $log->model_id);
    }

    private function material(): Material
    {
        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        return Material::create([
            'name' => 'Material A',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);
    }
}
