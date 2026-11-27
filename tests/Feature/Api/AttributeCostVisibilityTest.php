<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttributeCostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private User $designer;

    private User $manager;

    private User $owner;

    private Attribute $attribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->designer = $this->user('designer', $this->branchOne);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->owner = $this->user('owner', null);

        $this->attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 1500,
            'cost' => 700,
            'is_active' => true,
        ]);
    }

    public function test_designer_quote_options_omit_attribute_cost(): void
    {
        $attributes = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->json('attributes');

        $attribute = collect($attributes)->firstWhere('id', $this->attribute->id);

        $this->assertNotNull($attribute);
        $this->assertArrayNotHasKey('cost', $attribute);
        $this->assertSame('1500.00', $attribute['price']);
    }

    public function test_owner_and_manager_attribute_lists_keep_cost(): void
    {
        $global = Attribute::create([
            'branch_id' => null,
            'name' => 'Lamination',
            'type' => 'finishing',
            'price' => 2000,
            'cost' => 900,
            'is_active' => true,
        ]);

        foreach ([$this->owner, $this->manager] as $user) {
            $rows = $this->actingAs($user)
                ->getJson('/api/v1/attributes')
                ->assertOk()
                ->json('data');

            $branchRow = collect($rows)->firstWhere('id', $this->attribute->id);

            $this->assertNotNull($branchRow);
            $this->assertSame('700.00', $branchRow['cost']);
            $this->assertSame('1500.00', $branchRow['price']);

            $globalRow = collect($rows)->firstWhere('id', $global->id);

            $this->assertNotNull($globalRow);
            $this->assertSame('900.00', $globalRow['cost']);
        }
    }

    public function test_guest_and_designer_cannot_read_attribute_management_list(): void
    {
        $this->getJson('/api/v1/attributes')->assertUnauthorized();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/attributes')
            ->assertForbidden();
    }

    public function test_designer_quote_options_keep_attribute_price(): void
    {
        $attributes = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->json('attributes');

        $attribute = collect($attributes)->firstWhere('id', $this->attribute->id);

        $this->assertNotNull($attribute);
        $this->assertSame('1500.00', $attribute['price']);
        $this->assertArrayNotHasKey('cost', $attribute);
        $this->assertArrayNotHasKey('profit', $attribute);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
