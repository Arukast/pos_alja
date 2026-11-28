<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Support\AttributeTypeBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeTypeBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_types_are_seeded_with_the_legacy_slugs(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_types'));
        $this->assertTrue(Schema::hasColumns('attribute_types', [
            'id', 'name', 'slug', 'sort_order', 'is_active', 'created_at', 'updated_at',
        ]));
        $this->assertDatabaseHas('attribute_types', ['slug' => 'finishing', 'name' => 'Finishing', 'sort_order' => 1, 'is_active' => true]);
        $this->assertDatabaseHas('attribute_types', ['slug' => 'cut', 'name' => 'Potong', 'sort_order' => 2, 'is_active' => true]);
        $this->assertDatabaseHas('attribute_types', ['slug' => 'display', 'name' => 'Display', 'sort_order' => 3, 'is_active' => true]);
    }

    public function test_backfill_uses_the_exact_slug_and_a_headline_name(): void
    {
        $attribute = Attribute::create(['name' => 'Legacy Custom', 'type' => 'custom-type']);

        AttributeTypeBackfill::run();

        $type = AttributeType::where('slug', 'custom-type')->firstOrFail();
        $this->assertSame('Custom Type', $type->name);
        $this->assertSame(4, $type->sort_order);
        $this->assertTrue($type->is_active);
        $this->assertSame('custom-type', $attribute->fresh()?->type);
    }

    public function test_backfill_suffixes_a_colliding_headline_name(): void
    {
        Attribute::create(['name' => 'Spaced', 'type' => 'custom type']);
        Attribute::create(['name' => 'Hyphenated', 'type' => 'custom-type']);

        AttributeTypeBackfill::run();

        $names = [
            AttributeType::where('slug', 'custom type')->firstOrFail()->name,
            AttributeType::where('slug', 'custom-type')->firstOrFail()->name,
        ];

        $this->assertEqualsCanonicalizing(['Custom Type', 'Custom Type-2'], $names);
    }

    public function test_backfill_is_idempotent_and_does_not_overwrite_edits(): void
    {
        AttributeType::where('slug', 'cut')->firstOrFail()->update([
            'name' => 'Potong Khusus',
            'sort_order' => 9,
        ]);

        AttributeTypeBackfill::run();
        AttributeTypeBackfill::run();

        $this->assertSame(3, AttributeType::count());
        $this->assertDatabaseHas('attribute_types', [
            'slug' => 'cut',
            'name' => 'Potong Khusus',
            'sort_order' => 9,
        ]);
    }

    public function test_backfill_ignores_empty_type_values(): void
    {
        Attribute::create(['name' => 'No Type', 'type' => '']);

        AttributeTypeBackfill::run();

        $this->assertDatabaseMissing('attribute_types', ['slug' => '']);
        $this->assertSame(3, AttributeType::count());
    }

    public function test_backfill_leaves_existing_attributes_unchanged(): void
    {
        $branch = Branch::create(['name' => 'Cabang Uji']);

        $attribute = Attribute::create([
            'branch_id' => $branch->id,
            'name' => 'Legacy Finishing',
            'type' => 'finishing',
            'price' => '15000.00',
            'cost' => '9000.00',
            'is_active' => false,
        ]);

        AttributeTypeBackfill::run();

        $fresh = $attribute->fresh();

        $this->assertNotNull($fresh);
        $this->assertSame('Legacy Finishing', $fresh->name);
        $this->assertSame('finishing', $fresh->type);
        $this->assertSame($branch->id, $fresh->branch_id);
        $this->assertSame('15000.00', $fresh->price);
        $this->assertSame('9000.00', $fresh->cost);
        $this->assertFalse($fresh->is_active);
    }
}
