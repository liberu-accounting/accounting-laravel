<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoice_number is declared on the Invoice model and force-set by its
        // creating hook, but is absent from the active invoices schema. Nullable
        // + guarded so it coexists with any other migration adding the column.
        if (! Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->string('invoice_number')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('invoice_number');
            });
        }
    }
};
