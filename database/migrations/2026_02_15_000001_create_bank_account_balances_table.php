<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_connection_id')->constrained()->onDelete('cascade');
            $table->string('plaid_account_id');
            $table->string('account_name');
            $table->string('account_type'); // depository, credit, loan, investment, etc.
            $table->string('account_subtype')->nullable(); // checking, savings, credit card, etc.
            $table->decimal('current_balance', 15, 2)->nullable();
            $table->decimal('available_balance', 15, 2)->nullable();
            $table->decimal('limit_amount', 15, 2)->nullable(); // For credit cards
            $table->string('iso_currency_code', 3)->nullable(); // USD, EUR, etc.
            $table->string('unofficial_currency_code')->nullable(); // For cryptocurrencies
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('bank_connection_id');
            $table->unique(['bank_connection_id', 'plaid_account_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_account_balances');
    }
};
