<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The ExchangeRate model existed without a backing table — this adds it.
        if (Schema::hasTable('exchange_rates')) {
            return;
        }

        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id('exchange_rate_id');
            $table->unsignedBigInteger('from_currency_id');
            $table->unsignedBigInteger('to_currency_id');
            $table->decimal('rate', 20, 10);
            $table->date('date');
            $table->timestamps();

            $table->unique(['from_currency_id', 'to_currency_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
