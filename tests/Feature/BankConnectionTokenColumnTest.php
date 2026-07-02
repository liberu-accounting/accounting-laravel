<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BankConnectionTokenColumnTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards on ANY driver (SQLite included): the encrypted-token columns must be
     * TEXT, not VARCHAR(255), or the encrypted ciphertext overflows on MySQL/prod.
     */
    public function test_encrypted_token_columns_are_text(): void
    {
        foreach ([
            'plaid_access_token',
            'revolut_access_token',
            'revolut_refresh_token',
            'wise_access_token',
            'wise_refresh_token',
        ] as $col) {
            $this->assertSame(
                'text',
                Schema::getColumnType('bank_connections', $col),
                "bank_connections.{$col} must be TEXT to hold an encrypted token",
            );
        }
    }

    /**
     * A realistically long encrypted token round-trips. Fails on MySQL pre-fix
     * ("Data too long"); harmless on SQLite (no length enforcement).
     */
    public function test_long_encrypted_token_round_trips(): void
    {
        $long = str_repeat('x', 500);

        $connection = BankConnection::factory()->create(['plaid_access_token' => $long]);

        $this->assertSame($long, $connection->fresh()->plaid_access_token);
    }
}
