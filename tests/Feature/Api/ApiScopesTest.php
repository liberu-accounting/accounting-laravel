<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimates_read_token_allowed_on_read_blocked_on_write(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['estimates:read']);

        $this->getJson('/api/v1/estimates')->assertOk();
        $this->postJson('/api/v1/estimates', [])->assertForbidden();
    }

    public function test_estimates_token_blocked_on_other_resources(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['estimates:read']);

        $this->getJson('/api/v1/journal-entries')->assertForbidden();
        $this->getJson('/api/v1/chart-of-accounts')->assertForbidden();
        $this->getJson('/api/v1/general-ledger/trial-balance')->assertForbidden();
    }

    public function test_generated_openapi_covers_every_v1_route_with_scopes(): void
    {
        $spec = $this->getJson('/api/v1/openapi.json')->assertOk()->json();

        // Generated from live routes — these all appear.
        foreach (['/invoices', '/bills', '/estimates', '/chart-of-accounts', '/journal-entries', '/general-ledger/trial-balance'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths'], "spec missing {$path}");
        }

        // Each lists its required scope on the GET operation.
        $this->assertSame([['sanctum' => ['estimates:read']]], $spec['paths']['/estimates']['get']['security']);
        $this->assertSame([['sanctum' => ['general-ledger:read']]], $spec['paths']['/general-ledger/trial-balance']['get']['security']);
        // Write verb carries the :write scope.
        $this->assertSame([['sanctum' => ['estimates:write']]], $spec['paths']['/estimates']['post']['security']);
    }
}
