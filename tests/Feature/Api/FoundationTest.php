<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_api_routes_return_json_401_for_guests(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/nope')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/json');
    }
}
