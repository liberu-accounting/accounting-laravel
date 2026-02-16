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
        Schema::create('delayed_charges', function (Blueprint $table) {
            $table->id('delayed_charge_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('charge_date');
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'invoiced', 'void'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('set null');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('set null');
            
            $table->index('customer_id');
            $table->index('charge_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delayed_charges');
    }
};
