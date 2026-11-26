<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
