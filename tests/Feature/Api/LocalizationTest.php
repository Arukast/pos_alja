<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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
}
