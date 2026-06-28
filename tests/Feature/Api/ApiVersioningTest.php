<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_invoices_index_works_with_read_ability(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['invoices:read']);

        $this->getJson('/api/v1/invoices')->assertOk();
    }

    public function test_read_token_is_rejected_on_write(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['invoices:read']);

        // No write ability → forbidden.
        $this->postJson('/api/v1/invoices', [])->assertForbidden();
    }

    public function test_invoices_token_is_rejected_on_bills_scope(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['invoices:read']);

        $this->getJson('/api/v1/bills')->assertForbidden();
    }

    public function test_openapi_spec_is_served(): void
    {
        $this->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonStructure(['openapi', 'info', 'paths']);
    }
}
