<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_locale_is_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
    }

    public function test_validation_messages_are_indonesian(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', 'Kolom nama pengguna wajib diisi.');
    }

    public function test_auth_failure_message_is_indonesian(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'missing-user',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', 'Nama pengguna atau kata sandi salah.')
            ->assertJsonPath('message', 'Nama pengguna atau kata sandi salah.');
    }

    public function test_nested_attribute_validation_messages_use_translated_label(): void
    {
        $branch = Branch::create(['name' => 'Cabang Uji']);
        $user = User::factory()->create([
            'role' => 'designer',
            'branch_id' => $branch->id,
        ]);
        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);
        $product = Product::create([
            'name' => 'Vinyl Banner',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);
        $material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/quotes', [
            'product_id' => $product->id,
            'material_id' => $material->id,
            'selected_attributes' => ['not-a-uuid'],
            'quantity' => 1,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'selected_attributes.0' => 'Kolom atribut harus berupa UUID yang valid.',
            ]);
    }
}
