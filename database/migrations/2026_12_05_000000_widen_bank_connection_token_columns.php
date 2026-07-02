<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns on bank_connections that carry Laravel `encrypted`-cast values.
     * The ciphertext (base64 JSON envelope) runs ~200-400 chars for a token,
     * overflowing the original VARCHAR(255). SQLite ignores the length limit so
     * the test suite stayed green, but MySQL/prod rejects the insert with
     * "SQLSTATE[22001] Data too long" — so bank connections could not persist
     * their tokens in production. Widen to TEXT (sage/qbo/xero connections
     * already use TEXT for the same reason).
     */
    private const COLUMNS = [
        'plaid_access_token',
        'revolut_access_token',
        'revolut_refresh_token',
        'wise_access_token',
        'wise_refresh_token',
    ];

    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table): void {
            foreach (self::COLUMNS as $col) {
                if (Schema::hasColumn('bank_connections', $col)) {
                    $table->text($col)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table): void {
            foreach (self::COLUMNS as $col) {
                if (Schema::hasColumn('bank_connections', $col)) {
                    $table->string($col)->nullable()->change();
                }
            }
        });
    }
};
