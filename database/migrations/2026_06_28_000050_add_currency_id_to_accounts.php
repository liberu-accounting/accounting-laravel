<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account::getBalanceInCurrency() reads $account->currency, but the column
        // was never created (it's in the model's fillable only). Add it; null = the
        // reporting/default currency.
        if (! Schema::hasColumn('accounts', 'currency_id')) {
            Schema::table('accounts', function (Blueprint $table): void {
                $table->unsignedBigInteger('currency_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accounts', 'currency_id')) {
            Schema::table('accounts', function (Blueprint $table): void {
                $table->dropColumn('currency_id');
            });
        }
    }
};
