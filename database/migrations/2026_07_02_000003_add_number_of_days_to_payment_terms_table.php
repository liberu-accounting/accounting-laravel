<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_terms', 'payment_term_number_of_days')) {
                $table->integer('payment_term_number_of_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            $table->dropColumn('payment_term_number_of_days');
        });
    }
};
