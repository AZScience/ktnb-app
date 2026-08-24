<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    public function test_protected_endpoint_rejects_missing_api_key(): void
    {
        $this->getJson('/api/v1/discussions')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized. Invalid API Key.',
            ]);
    }

    public function test_protected_endpoint_rejects_invalid_api_key(): void
    {
        $this->getJson('/api/v1/discussions', [
            'x-api-key' => 'wrong-key',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized. Invalid API Key.',
            ]);
    }

    public function test_protected_endpoint_accepts_valid_api_key(): void
    {
        $this->getJson('/api/v1/discussions', [
            'x-api-key' => 'phpunit-test-api-key',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_public_poll_endpoint_does_not_require_api_key(): void
    {
        $this->getJson('/api/v1/polls/non-existent-id')
            ->assertNotFound();
    }
}
