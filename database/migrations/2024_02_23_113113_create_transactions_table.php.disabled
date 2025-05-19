<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->integer('transaction_id', true);
            $table->integer('customer_id');
            $table->date('transaction_date');
            $table->text('transaction_description');
            $table->decimal('amount', 10, 2);
            $table->integer('debit_account_id');
            $table->integer('credit_account_id'); 
            $table->timestamps();

            $table->foreign('debit_account_id')->references('account_id')->on('accounts');
            $table->foreign('credit_account_id')->references('account_id')->on('accounts');
            $table->foreign('customer_id')->references('customer_id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
