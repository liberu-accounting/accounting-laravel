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
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id('sales_receipt_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('sales_receipt_number')->unique();
            $table->date('sales_receipt_date');
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('deposit_to_account_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed', 'void'])->default('completed');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('tax_rate_id')->references('tax_rate_id')->on('tax_rates')->onDelete('set null');
            $table->foreign('deposit_to_account_id')->references('account_id')->on('accounts')->onDelete('set null');
            
            $table->index('customer_id');
            $table->index('sales_receipt_date');
            $table->index('status');
        });

        Schema::create('sales_receipt_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('sales_receipt_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);

            $table->foreign('sales_receipt_id')->references('sales_receipt_id')->on('sales_receipts')->onDelete('cascade');
            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('set null');
            
            $table->index('sales_receipt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_receipt_items');
        Schema::dropIfExists('sales_receipts');
    }
};
