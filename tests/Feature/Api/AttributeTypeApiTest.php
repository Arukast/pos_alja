<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\User;
use App\Policies\AttributeTypePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttributeTypeApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branch);
        $this->cashier = $this->user('cashier', $this->branch);
        $this->designer = $this->user('designer', $this->branch);
    }

    public function test_guest_cannot_list_attribute_types(): void
    {
        $this->getJson('/api/v1/attribute-types')->assertUnauthorized();
    }

    public function test_guest_and_unauthorized_roles_cannot_write_types(): void
    {
        $this->postJson('/api/v1/attribute-types', ['name' => 'Guest'])->assertUnauthorized();
        $this->putJson('/api/v1/attribute-types/cut', ['name' => 'Guest'])->assertUnauthorized();

        foreach ([$this->designer, $this->cashier] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/attribute-types', ['name' => 'Forbidden'])
                ->assertForbidden();

            $this->actingAs($user)
                ->putJson('/api/v1/attribute-types/cut', ['name' => 'Forbidden'])
                ->assertForbidden();
        }
    }

    public function test_owner_and_manager_list_types_ordered_by_sort_then_name(): void
    {
        foreach ([$this->owner, $this->manager] as $user) {
            $data = $this->actingAs($user)
                ->getJson('/api/v1/attribute-types')
                ->assertOk()
                ->json('data');

            $this->assertSame(['finishing', 'cut', 'display'], array_column($data, 'slug'));

            foreach (['id', 'name', 'slug', 'sort_order', 'is_active'] as $key) {
                $this->assertArrayHasKey($key, $data[0]);
            }

            $this->assertSame(1, $data[0]['sort_order']);
            $this->assertTrue($data[0]['is_active']);
        }
    }

    public function test_designer_and_cashier_cannot_list_types(): void
    {
        $this->actingAs($this->designer)
            ->getJson('/api/v1/attribute-types')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/attribute-types')
            ->assertForbidden();
    }

    public function test_branchless_manager_cannot_list_types(): void
    {
        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->getJson('/api/v1/attribute-types')
            ->assertForbidden();
    }

    public function test_production_cannot_list_types(): void
    {
        $production = $this->user('production', $this->branch);

        $this->actingAs($production)
            ->getJson('/api/v1/attribute-types')
            ->assertForbidden();
    }

    public function test_owner_creates_a_type_with_generated_slug_and_appended_order(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'UV Coating'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.name', 'UV Coating')
            ->assertJsonPath('attribute_type.slug', 'uv-coating')
            ->assertJsonPath('attribute_type.sort_order', 4)
            ->assertJsonPath('attribute_type.is_active', true);

        $this->assertDatabaseHas('attribute_types', [
            'name' => 'UV Coating',
            'slug' => 'uv-coating',
            'sort_order' => 4,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Packaging'])
            ->assertCreated();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Packaging'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Packaging Two'])
            ->assertCreated();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/packaging-two', ['name' => 'Packaging'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/packaging', ['name' => 'Packaging'])
            ->assertOk();
    }

    public function test_slug_collision_gets_a_numeric_suffix(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Packaging'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.slug', 'packaging');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Packaging!'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.slug', 'packaging-2');
    }

    public function test_slug_is_immutable(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/cut', ['name' => 'Cutting', 'slug' => 'changed'])
            ->assertOk()
            ->assertJsonPath('attribute_type.slug', 'cut')
            ->assertJsonPath('attribute_type.name', 'Cutting');

        $this->assertDatabaseHas('attribute_types', ['slug' => 'cut', 'name' => 'Cutting']);
        $this->assertDatabaseMissing('attribute_types', ['slug' => 'changed']);
    }

    public function test_long_slug_collision_stays_within_100_characters(): void
    {
        AttributeType::create([
            'name' => 'Held',
            'slug' => str_repeat('a', 100),
            'sort_order' => 10,
        ]);

        $slug = $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => str_repeat('a', 100)])
            ->assertCreated()
            ->json('attribute_type.slug');

        $this->assertIsString($slug);
        $this->assertSame(100, strlen($slug));
        $this->assertStringStartsWith(str_repeat('a', 98), $slug);
        $this->assertStringEndsWith('-2', $slug);
    }

    public function test_empty_slug_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => '!!!'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_expanding_slug_is_truncated_to_100_characters(): void
    {
        $slug = $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => str_repeat('ß', 60)])
            ->assertCreated()
            ->json('attribute_type.slug');

        $this->assertIsString($slug);
        $this->assertLessThanOrEqual(100, strlen($slug));
        $this->assertSame(100, strlen($slug));
    }

    public function test_validation_rejects_invalid_type_payloads(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => str_repeat('a', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Negative Order', 'sort_order' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_order');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Overflow Order', 'sort_order' => 2147483648])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_order');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Null Active', 'is_active' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_explicit_zero_sort_order_is_preserved_on_create_and_update(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Zero', 'sort_order' => 0])
            ->assertCreated()
            ->assertJsonPath('attribute_type.sort_order', 0);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/cut', ['name' => 'Cut', 'sort_order' => 0])
            ->assertOk()
            ->assertJsonPath('attribute_type.sort_order', 0);

        $this->assertDatabaseHas('attribute_types', ['slug' => 'cut', 'sort_order' => 0]);
    }

    public function test_sort_order_append_is_clamped_at_the_integer_maximum(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Maximum', 'sort_order' => 2147483647])
            ->assertCreated()
            ->assertJsonPath('attribute_type.sort_order', 2147483647);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Overflow'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.sort_order', 2147483647);
    }

    public function test_equal_sort_order_ties_break_by_name(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Beta', 'sort_order' => 5])
            ->assertCreated();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Alpha', 'sort_order' => 5])
            ->assertCreated();

        $slugs = array_column(
            $this->actingAs($this->manager)
                ->getJson('/api/v1/attribute-types')
                ->assertOk()
                ->json('data'),
            'slug'
        );

        $this->assertSame(['finishing', 'cut', 'display', 'alpha', 'beta'], $slugs);
    }

    public function test_sort_order_can_be_set_and_is_preserved_when_omitted(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Explicit', 'sort_order' => 9])
            ->assertCreated()
            ->assertJsonPath('attribute_type.sort_order', 9);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/explicit', ['name' => 'Explicit Renamed'])
            ->assertOk()
            ->assertJsonPath('attribute_type.sort_order', 9);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Appended'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.sort_order', 10);
    }

    public function test_active_defaults_true_and_can_be_toggled(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Active Default'])
            ->assertCreated()
            ->assertJsonPath('attribute_type.is_active', true);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => 'Inactive', 'is_active' => false])
            ->assertCreated()
            ->assertJsonPath('attribute_type.is_active', false);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/inactive', ['name' => 'Inactive', 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('attribute_type.is_active', true);
    }

    public function test_manager_cannot_create_or_update_types(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/v1/attribute-types', ['name' => 'Manager Type'])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->putJson('/api/v1/attribute-types/cut', ['name' => 'Manager Cut'])
            ->assertForbidden();

        $this->assertDatabaseMissing('attribute_types', ['name' => 'Manager Type']);
    }

    public function test_update_binds_by_slug_and_unknown_slug_is_404(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/cut', ['name' => 'Cut'])
            ->assertOk()
            ->assertJsonPath('attribute_type.slug', 'cut')
            ->assertJsonPath('attribute_type.name', 'Cut');

        $this->actingAs($this->owner)
            ->putJson('/api/v1/attribute-types/does-not-exist', ['name' => 'Nope'])
            ->assertNotFound();
    }

    public function test_delete_is_a_404_tombstone_and_keeps_the_row(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/attribute-types/finishing')
            ->assertNotFound();

        $this->assertDatabaseHas('attribute_types', ['slug' => 'finishing']);
    }

    public function test_policy_is_registered(): void
    {
        $this->assertInstanceOf(
            AttributeTypePolicy::class,
            Gate::getPolicyFor(AttributeType::class)
        );
    }

    public function test_long_slug_round_trips_through_an_attribute(): void
    {
        $slug = $this->actingAs($this->owner)
            ->postJson('/api/v1/attribute-types', ['name' => str_repeat('long', 25)])
            ->assertCreated()
            ->json('attribute_type.slug');

        $this->assertIsString($slug);
        $this->assertSame(100, strlen($slug));

        $attribute = Attribute::create([
            'name' => 'Long Type Attribute',
            'type' => $slug,
        ]);

        $row = collect(
            $this->actingAs($this->owner)
                ->getJson('/api/v1/attributes')
                ->assertOk()
                ->json('data')
        )->firstWhere('id', $attribute->id);

        $this->assertNotNull($row);
        $this->assertSame($slug, $row['type']);
        $this->assertSame(100, strlen((string) $attribute->fresh()?->type));
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
