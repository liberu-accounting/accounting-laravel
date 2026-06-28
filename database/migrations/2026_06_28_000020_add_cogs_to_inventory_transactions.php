<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // InventoryService writes cost_of_goods_sold on a sale, but the column
        // was never created — every sale would throw. Add it.
        if (! Schema::hasColumn('inventory_transactions', 'cost_of_goods_sold')) {
            Schema::table('inventory_transactions', function (Blueprint $table): void {
                $table->decimal('cost_of_goods_sold', 15, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_transactions', 'cost_of_goods_sold')) {
            Schema::table('inventory_transactions', function (Blueprint $table): void {
                $table->dropColumn('cost_of_goods_sold');
            });
        }
    }
};
