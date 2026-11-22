<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ExceptionRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_requests_receive_an_indonesian_401_message(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Silakan masuk terlebih dahulu.');
    }

    public function test_unauthorized_requests_receive_an_indonesian_403_message(): void
    {
        $branch = Branch::create(['name' => 'Cabang Satu']);
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($cashier)
            ->getJson('/api/v1/branches')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk tindakan ini.');
    }

    public function test_missing_models_receive_an_indonesian_404_message(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);

        $this->actingAs($owner)
            ->getJson('/api/v1/invoices/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('message', 'Data yang diminta tidak ditemukan.');
    }

    public function test_unknown_api_routes_receive_an_indonesian_404_message(): void
    {
        $this->getJson('/api/v1/_not_a_route')
            ->assertNotFound()
            ->assertJsonPath('message', 'Data yang diminta tidak ditemukan.');
    }

    public function test_app_authored_403_messages_survive_rendering(): void
    {
        Route::get('/api/v1/_abort_with_message', static function (): void {
            abort(403, 'Desainer harus ditugaskan ke cabang.');
        });

        Route::get('/api/v1/_abort_without_message', static function (): void {
            abort(403);
        });

        $this->getJson('/api/v1/_abort_with_message')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Desainer harus ditugaskan ke cabang.']);

        $this->getJson('/api/v1/_abort_without_message')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki izin untuk tindakan ini.']);
    }

    public function test_server_errors_hide_internal_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::get('/api/v1/_boom', static function (): void {
            throw new RuntimeException('internal-marker');
        });

        $response = $this->getJson('/api/v1/_boom')->assertStatus(500);

        $response->assertExactJson(['message' => 'Terjadi kesalahan pada server.']);
        $this->assertStringNotContainsString('internal-marker', (string) $response->getContent());
    }

    public function test_throttled_login_keeps_its_indonesian_message(): void
    {
        User::factory()->create([
            'username' => 'throttled-user',
            'role' => 'cashier',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'throttled-user',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'throttled-user',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertMatchesRegularExpression(
            '/^Terlalu banyak percobaan masuk\. Silakan coba lagi dalam \d+ detik\.$/',
            (string) $response->json('message')
        );
        $this->assertStringNotContainsString('Too Many Requests', (string) $response->getContent());
    }

    public function test_non_json_requests_keep_the_html_not_found_response(): void
    {
        $response = $this->get('/_missing_page');

        $response->assertNotFound();
        $this->assertStringStartsWith('text/html', (string) $response->headers->get('content-type'));
    }

    public function test_validation_errors_keep_their_errors_payload(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonStructure(['message', 'errors' => ['username']]);
    }
}
