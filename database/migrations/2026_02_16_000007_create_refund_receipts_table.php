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
        Schema::create('refund_receipts', function (Blueprint $table) {
            $table->id('refund_receipt_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sales_receipt_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('refund_receipt_number')->unique();
            $table->date('refund_date');
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('refund_from_account_id')->nullable();
            $table->enum('reason', ['product_return', 'overpayment', 'service_not_rendered', 'customer_dissatisfaction', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed', 'void'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('sales_receipt_id')->references('sales_receipt_id')->on('sales_receipts')->onDelete('set null');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('set null');
            $table->foreign('refund_from_account_id')->references('account_id')->on('accounts')->onDelete('set null');
            
            $table->index('customer_id');
            $table->index('refund_date');
            $table->index('status');
        });

        Schema::create('refund_receipt_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('refund_receipt_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);

            $table->foreign('refund_receipt_id')->references('refund_receipt_id')->on('refund_receipts')->onDelete('cascade');
            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('set null');
            
            $table->index('refund_receipt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_receipt_items');
        Schema::dropIfExists('refund_receipts');
    }
};
