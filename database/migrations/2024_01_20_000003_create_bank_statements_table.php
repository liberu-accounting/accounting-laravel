<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->date('statement_date');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->decimal('total_credits', 15, 2)->default(0);
            $table->decimal('total_debits', 15, 2)->default(0);
            $table->decimal('ending_balance', 15, 2)->default(0);
            $table->boolean('reconciled')->default(false);
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
