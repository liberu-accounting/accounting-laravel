<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_present_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_strict_transport_security_header_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_permissions_policy_header_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy');
    }
}
