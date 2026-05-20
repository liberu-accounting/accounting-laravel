<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('transaction_date')->nullable();
            $table->text('transaction_description')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->nullable();
            $table->string('transaction_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('bank_statement_id')->nullable();
            $table->boolean('reconciled')->default(false);
            $table->text('discrepancy_notes')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id')->nullable()->unique();
            $table->foreignId('bank_connection_id')->nullable()->constrained()->onDelete('set null');
            $table->string('category')->nullable();
            $table->string('status')->default('posted');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}
