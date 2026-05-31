<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_on_web_route(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_on_api_route(): void
    {
        $response = $this->getJson('/api/user');

        // 401 but headers should still be present
        $response->assertStatus(401);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_csrf_token_in_meta_on_web(): void
    {
        $response = $this->get('/');
        $response->assertSee('csrf-token', false);
    }

    public function test_unauthenticated_api_returns_401(): void
    {
        $this->getJson('/api/transactions')->assertUnauthorized();
        $this->getJson('/api/plaid/connections')->assertUnauthorized();
        $this->getJson('/api/wise/connections')->assertUnauthorized();
    }

    public function test_authenticated_api_user_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    }
}
