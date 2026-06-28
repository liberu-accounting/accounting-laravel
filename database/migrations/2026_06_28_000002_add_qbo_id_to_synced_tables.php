<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // invoice_number is declared on the Invoice model but absent from the
            // active invoices schema; QBO DocNumber maps onto it. Nullable so it
            // never breaks inserts on rows created without one.
            if (! Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number')->nullable();
            }
            $table->string('qbo_id')->nullable()->index();
            $table->string('qbo_sync_token')->nullable();
        });

        Schema::table('accounts', function (Blueprint $table): void {
            $table->string('qbo_id')->nullable()->index();
            $table->string('qbo_sync_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['qbo_id', 'qbo_sync_token']);
        });

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn(['qbo_id', 'qbo_sync_token']);
        });
    }
};
