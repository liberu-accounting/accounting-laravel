<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateCustomerTaxRateTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_tax_rate')) {
            Schema::create('customer_tax_rate', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('tax_rate_id')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('customer_tax_rate', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_tax_rate', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
            if (! Schema::hasColumn('customer_tax_rate', 'tax_rate_id')) {
                $table->unsignedBigInteger('tax_rate_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tax_rate');
    }
}
