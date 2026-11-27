<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Feature coverage for customer creation and search in the Sales module. */
final class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    /** Guests cannot create customers. */
    public function test_guest_cannot_create_customer(): void
    {
        $this->postJson('/api/v1/customers', [])->assertUnauthorized();
    }

    /** Designers may create a customer with required name and institution only. */
    public function test_designer_creates_customer_with_optional_contact_fields(): void
    {
        $branch = Branch::create(['name' => 'Customer Branch', 'code' => 'CUST']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ])->assertCreated()
            ->assertJsonPath('customer.name', 'Sekolah Contoh')
            ->assertJsonPath('customer.institution', 'Sekolah')
            ->assertJsonPath('customer.phone', null)
            ->assertJsonPath('customer.email', null);
    }

    /** Name and institution are required, and the institution must use an allowed category. */
    public function test_customer_requires_name_and_allowed_institution(): void
    {
        $branch = Branch::create(['name' => 'Validation Branch', 'code' => 'VAL']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'phone' => '0812345678',
            'institution' => 'Lainnya',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'institution']);
    }

    /** The customer type is limited to the supported pricing tiers. */
    public function test_customer_type_must_be_reguler_or_reseller(): void
    {
        $branch = Branch::create(['name' => 'Type Branch', 'code' => 'TYPE']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Mitra Contoh',
            'institution' => 'Sekolah',
            'type' => 'Mitra',
        ])->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    /** Creating a customer records the actor, branch, and contact fields. */
    public function test_customer_creation_writes_create_audit(): void
    {
        $branch = Branch::create(['name' => 'Audit Branch', 'code' => 'AUD']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $response = $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Sekolah Audit',
            'phone' => '0812345678',
            'email' => 'audit@example.test',
            'address' => 'Jalan Audit 1',
            'institution' => 'Sekolah',
        ])->assertCreated();

        $customerId = (string) $response->json('customer.id');

        $log = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Customer::class)
            ->where('model_id', $customerId)
            ->firstOrFail();

        $this->assertSame($designer->id, $log->user_id);
        $this->assertSame($designer->username, $log->username);
        $this->assertSame('designer', $log->role);
        $this->assertSame($branch->id, $log->branch_id);

        $this->assertSame('Sekolah Audit', $log->payload['name']);
        $this->assertSame('0812345678', $log->payload['phone']);
        $this->assertSame('audit@example.test', $log->payload['email']);
        $this->assertSame('Jalan Audit 1', $log->payload['address']);
        $this->assertSame('Sekolah', $log->payload['institution']);
        $this->assertSame(['Sekolah'], $log->payload['institutions']);
        $this->assertSame('Reguler', $log->payload['type']);

        $this->assertSame(1, ActivityLog::query()
            ->where('model_type', Customer::class)
            ->where('model_id', $customerId)
            ->count());
    }

    /** Rejected or invalid customer writes leave no audit row. */
    public function test_unauthorized_and_invalid_customer_writes_do_not_write_audit(): void
    {
        $branch = Branch::create(['name' => 'Guard Branch', 'code' => 'GRD']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);
        $production = User::factory()->create(['role' => 'production', 'branch_id' => $branch->id]);

        $this->postJson('/api/v1/customers', [
            'name' => 'Guest',
            'institution' => 'Sekolah',
        ])->assertUnauthorized();

        $this->actingAs($production)->postJson('/api/v1/customers', [
            'name' => 'Blocked',
            'institution' => 'Sekolah',
        ])->assertForbidden();

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Tanpa Institusi',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** A reseller customer keeps the type that drives the reseller discount path. */
    public function test_designer_creates_reseller_customer(): void
    {
        $branch = Branch::create(['name' => 'Reseller Branch', 'code' => 'RSL']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Toko Reseller',
            'institution' => 'Masyarakat/Retail',
            'type' => 'Reseller',
        ])->assertCreated()->assertJsonPath('customer.type', 'Reseller');
    }

    /** Production cannot create or search customer records. */
    public function test_production_cannot_create_customer(): void
    {
        $branch = Branch::create(['name' => 'Production Branch', 'code' => 'PROD']);
        $production = User::factory()->create(['role' => 'production', 'branch_id' => $branch->id]);

        $this->actingAs($production)->postJson('/api/v1/customers', [
            'name' => 'Blocked',
            'institution' => 'Universitas',
        ])->assertForbidden();
    }

    /** Customer resource exposes institution and contact fields in search results. */
    public function test_customer_search_returns_institution_and_address(): void
    {
        $branch = Branch::create(['name' => 'Search Branch', 'code' => 'SRCH']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);
        $customer = Customer::create([
            'name' => 'Universitas Contoh',
            'institution' => 'Universitas',
            'address' => 'Jalan Contoh',
        ]);

        $this->actingAs($designer)->getJson('/api/v1/customers?search=Universitas')
            ->assertOk()
            ->assertJsonFragment(['id' => $customer->id, 'institution' => 'Universitas', 'address' => 'Jalan Contoh']);
    }

    /** Legacy singular payloads keep working and unknown labels 422 on the carrying field. */
    public function test_legacy_singular_institution_still_works_and_invalid_label_is_rejected(): void
    {
        $branch = Branch::create(['name' => 'Legacy Branch', 'code' => 'LEG']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Legacy Sekolah',
            'institution' => 'Sekolah',
        ])->assertCreated()
            ->assertJsonPath('customer.institution', 'Sekolah')
            ->assertJsonPath('customer.institutions', ['Sekolah']);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Legacy Lainnya',
            'institution' => 'Lainnya',
        ])->assertUnprocessable()->assertJsonValidationErrors(['institution']);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Array Lainnya',
            'institutions' => ['Lainnya'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['institutions.0']);
    }

    /** When both label fields are sent, the array wins over the legacy field. */
    public function test_institutions_wins_when_both_fields_are_sent(): void
    {
        $branch = Branch::create(['name' => 'Both Branch', 'code' => 'BTH']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $response = $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Both Fields',
            'institution' => 'Universitas',
            'institutions' => ['Sekolah', 'Universitas'],
        ])->assertCreated()
            ->assertJsonPath('customer.institution', 'Sekolah')
            ->assertJsonPath('customer.institutions', ['Sekolah', 'Universitas']);

        $customer = Customer::query()->findOrFail($response->json('customer.id'));
        $this->assertSame(['Sekolah', 'Universitas'], $customer->institutions);
    }

    /** Labels are stored in canonical constant order regardless of request order. */
    public function test_institutions_are_normalized_to_constant_order(): void
    {
        $branch = Branch::create(['name' => 'Order Branch', 'code' => 'ORD']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $response = $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Ordered Labels',
            'institutions' => ['Organisasi/Yayasan', 'Masyarakat/Retail'],
        ])->assertCreated()
            ->assertJsonPath('customer.institutions', ['Masyarakat/Retail', 'Organisasi/Yayasan']);

        $customer = Customer::query()->findOrFail($response->json('customer.id'));
        $this->assertSame(['Masyarakat/Retail', 'Organisasi/Yayasan'], $customer->institutions);
        $this->assertSame('Masyarakat/Retail', $customer->institution);
    }

    /** At least one label must be supplied through either field. */
    public function test_empty_or_missing_institutions_are_rejected(): void
    {
        $branch = Branch::create(['name' => 'Empty Branch', 'code' => 'EMP']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Empty Labels',
            'institutions' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['institutions']);

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'No Labels',
        ])->assertUnprocessable()->assertJsonValidationErrors(['institution', 'institutions']);
    }

    /** Owner and manager may create customers directly. */
    public function test_owner_and_manager_can_create_customer(): void
    {
        $branch = Branch::create(['name' => 'Write Branch', 'code' => 'WRT']);

        foreach (['owner', 'manager'] as $role) {
            $user = User::factory()->create(['role' => $role, 'branch_id' => $branch->id]);

            $this->actingAs($user)->postJson('/api/v1/customers', [
                'name' => ucfirst($role).' Created',
                'institution' => 'Sekolah',
            ])->assertCreated()->assertJsonPath('customer.institution', 'Sekolah');
        }
    }

    /** Cashiers have read-only customer access. */
    public function test_cashier_cannot_create_customer(): void
    {
        $branch = Branch::create(['name' => 'Cashier Branch', 'code' => 'CSH']);
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier)->postJson('/api/v1/customers', [
            'name' => 'Blocked By Cashier',
            'institution' => 'Sekolah',
        ])->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** Guests cannot authenticate and production cannot read or create customers. */
    public function test_guest_and_production_cannot_read_or_create_customers(): void
    {
        $this->getJson('/api/v1/customers')->assertUnauthorized();
        $this->postJson('/api/v1/customers', [
            'name' => 'Guest Create',
            'institution' => 'Sekolah',
        ])->assertUnauthorized();

        $branch = Branch::create(['name' => 'Guard Branch', 'code' => 'GBD']);
        $production = User::factory()->create(['role' => 'production', 'branch_id' => $branch->id]);

        $this->actingAs($production)->getJson('/api/v1/customers')->assertForbidden();
        $this->actingAs($production)->postJson('/api/v1/customers', [
            'name' => 'Production Create',
            'institution' => 'Sekolah',
        ])->assertForbidden();
    }

    /** Owner, manager and designer may update a customer's labels in canonical order. */
    public function test_owner_manager_and_designer_can_update_customer(): void
    {
        $branch = Branch::create(['name' => 'Update Branch', 'code' => 'UPD']);
        $customer = Customer::create([
            'name' => 'Pelanggan Lama',
            'institution' => 'Sekolah',
            'institutions' => ['Sekolah'],
        ]);

        foreach (['owner', 'manager', 'designer'] as $role) {
            $user = User::factory()->create(['role' => $role, 'branch_id' => $branch->id]);

            $this->actingAs($user)->putJson("/api/v1/customers/{$customer->id}", [
                'name' => ucfirst($role).' Updated',
                'institutions' => ['Bisnis/Perusahaan', 'Masyarakat/Retail'],
                'phone' => '0812345678',
            ])->assertOk()
                ->assertJsonPath('customer.name', ucfirst($role).' Updated')
                ->assertJsonPath('customer.phone', '0812345678')
                ->assertJsonPath('customer.institution', 'Masyarakat/Retail')
                ->assertJsonPath('customer.institutions', ['Masyarakat/Retail', 'Bisnis/Perusahaan']);
        }
    }

    /** Cashier and production cannot update a customer, leaving data and audit untouched. */
    public function test_cashier_and_production_cannot_update_customer(): void
    {
        $branch = Branch::create(['name' => 'Readonly Branch', 'code' => 'RO']);
        $customer = Customer::create([
            'name' => 'Pelanggan Terkunci',
            'institution' => 'Sekolah',
            'institutions' => ['Sekolah'],
            'phone' => '0811111111',
        ]);

        foreach (['cashier', 'production'] as $role) {
            $user = User::factory()->create(['role' => $role, 'branch_id' => $branch->id]);

            $this->actingAs($user)->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Blocked By '.$role,
                'institutions' => ['Universitas'],
            ])->assertForbidden();
        }

        $customer->refresh();
        $this->assertSame('Pelanggan Terkunci', $customer->name);
        $this->assertSame('Sekolah', $customer->institution);
        $this->assertSame(['Sekolah'], $customer->institutions);
        $this->assertSame('0811111111', $customer->phone);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** Guests cannot update a customer. */
    public function test_guest_cannot_update_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Guest Target',
            'institution' => 'Sekolah',
            'institutions' => ['Sekolah'],
        ]);

        $this->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Guest Update',
            'institutions' => ['Universitas'],
        ])->assertUnauthorized();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** Updating an unknown customer returns 404 without writing an audit row. */
    public function test_unknown_customer_update_returns_404(): void
    {
        $branch = Branch::create(['name' => 'Missing Branch', 'code' => 'MIS']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);

        $this->actingAs($designer)->putJson('/api/v1/customers/'.Str::uuid()->toString(), [
            'name' => 'Missing Customer',
            'institutions' => ['Universitas'],
        ])->assertNotFound();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** Updating a customer writes one UPDATE audit row with actor, branch and labels. */
    public function test_update_writes_single_update_audit_with_branch(): void
    {
        $branch = Branch::create(['name' => 'Update Audit Branch', 'code' => 'UAD']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);
        $customer = Customer::create([
            'name' => 'Audit Update',
            'institution' => 'Sekolah',
            'institutions' => ['Sekolah'],
        ]);

        $this->actingAs($designer)->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Audit Update Baru',
            'institutions' => ['Organisasi/Yayasan', 'Bisnis/Perusahaan'],
        ])->assertOk();

        $log = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Customer::class)
            ->where('model_id', $customer->id)
            ->firstOrFail();

        $this->assertSame($designer->id, $log->user_id);
        $this->assertSame($designer->username, $log->username);
        $this->assertSame('designer', $log->role);
        $this->assertSame($branch->id, $log->branch_id);
        $this->assertSame(['Bisnis/Perusahaan', 'Organisasi/Yayasan'], $log->payload['institutions']);

        $this->assertSame(1, ActivityLog::query()
            ->where('model_type', Customer::class)
            ->where('model_id', $customer->id)
            ->count());
    }

    /** Invalid label payloads are rejected without mutating the customer or auditing. */
    public function test_update_validation_rejects_invalid_labels(): void
    {
        $branch = Branch::create(['name' => 'Invalid Branch', 'code' => 'INV']);
        $designer = User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);
        $customer = Customer::create([
            'name' => 'Valid Labels',
            'institution' => 'Sekolah',
            'institutions' => ['Sekolah'],
        ]);

        $this->actingAs($designer)->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Invalid Singular',
            'institution' => 'Lainnya',
        ])->assertUnprocessable()->assertJsonValidationErrors(['institution']);

        $this->actingAs($designer)->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Invalid Array',
            'institutions' => ['Lainnya'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['institutions.0']);

        $customer->refresh();
        $this->assertSame('Valid Labels', $customer->name);
        $this->assertSame(['Sekolah'], $customer->institutions);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
