<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Covers required customer identity data and optional contact details for PB-02. */
final class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    /** Guest customer creation is rejected. */
    public function test_guest_cannot_create_customer(): void
    {
        $this->postJson('/api/v1/customers', [])->assertUnauthorized();
    }

    /** Designer may create a customer with only the required name and institution. */
    public function test_designer_can_create_customer_without_optional_contacts(): void
    {
        $designer = $this->designer();

        $this->actingAs($designer)->postJson('/api/v1/customers', [
            'name' => 'Sekolah Contoh',
            'institution' => 'Sekolah',
        ])->assertCreated()
            ->assertJsonPath('customer.name', 'Sekolah Contoh')
            ->assertJsonPath('customer.institution', 'Sekolah')
            ->assertJsonPath('customer.phone', null)
            ->assertJsonPath('customer.email', null);
    }

    /** Name and institution are required, and institution must match the supported list. */
    public function test_name_and_valid_institution_are_required(): void
    {
        $this->actingAs($this->designer())->postJson('/api/v1/customers', [
            'phone' => '0812345678',
            'institution' => 'Lainnya',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'institution']);
    }

    /** Production role cannot create customer records. */
    public function test_production_cannot_create_customer(): void
    {
        $branch = $this->branch();
        $production = User::factory()->create(['role' => 'production', 'branch_id' => $branch->id]);

        $this->actingAs($production)->postJson('/api/v1/customers', [
            'name' => 'Blocked',
            'institution' => 'Universitas',
        ])->assertForbidden();
    }

    /** Customer searches include institution and address in the response resource. */
    public function test_customer_search_exposes_institution_and_address(): void
    {
        $customer = Customer::create([
            'name' => 'Universitas Contoh',
            'institution' => 'Universitas',
            'address' => 'Jalan Contoh',
        ]);

        $this->actingAs($this->designer())->getJson('/api/v1/customers?search=Universitas')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $customer->id,
                'institution' => 'Universitas',
                'address' => 'Jalan Contoh',
            ]);
    }

    /** Creates a designer attached to a test branch. */
    private function designer(): User
    {
        $branch = $this->branch();

        return User::factory()->create(['role' => 'designer', 'branch_id' => $branch->id]);
    }

    /** Creates a branch row for the feature fixture. */
    private function branch(): Branch
    {
        return Branch::create(['name' => 'Customer Test Branch', 'code' => 'CTB']);
    }
}
