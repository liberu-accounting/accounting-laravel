<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The TaxRate model has always declared is_active/is_compound/description in
 * $fillable + $casts, but the columns were never created. So $this->is_active
 * read null and calculateTax() short-circuited to 0 for every rate (P0-8),
 * silently under-taxing every document. This adds the missing columns and
 * backfills existing rows to active so live rates start taxing correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $table): void {
            if (! Schema::hasColumn('tax_rates', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('tax_rates', 'is_compound')) {
                $table->boolean('is_compound')->default(false);
            }

            if (! Schema::hasColumn('tax_rates', 'description')) {
                $table->string('description')->nullable();
            }
        });

        // Backfill: existing rates must be active, not 0-taxed.
        DB::table('tax_rates')->update(['is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table): void {
            foreach (['is_active', 'is_compound', 'description'] as $column) {
                if (Schema::hasColumn('tax_rates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
