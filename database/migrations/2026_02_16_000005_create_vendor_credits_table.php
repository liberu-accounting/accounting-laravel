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
        Schema::create('vendor_credits', function (Blueprint $table) {
            $table->id('vendor_credit_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_credit_number')->unique();
            $table->date('credit_date');
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_applied', 15, 2)->default(0);
            $table->decimal('amount_remaining', 15, 2)->default(0);
            $table->enum('reason', ['product_return', 'overpayment', 'billing_error', 'discount', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'open', 'partial', 'applied', 'void'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('bill_id')->references('bill_id')->on('bills')->onDelete('set null');
            $table->foreign('tax_rate_id')->references('tax_rate_id')->on('tax_rates')->onDelete('set null');
            
            $table->index('vendor_id');
            $table->index('credit_date');
            $table->index('status');
        });

        Schema::create('vendor_credit_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('vendor_credit_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);

            $table->foreign('vendor_credit_id')->references('vendor_credit_id')->on('vendor_credits')->onDelete('cascade');
            $table->foreign('account_id')->references('account_id')->on('accounts')->onDelete('set null');
            
            $table->index('vendor_credit_id');
        });

        Schema::create('vendor_credit_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->unsignedBigInteger('vendor_credit_id');
            $table->unsignedBigInteger('bill_id');
            $table->decimal('amount_applied', 15, 2);
            $table->date('application_date');
            $table->timestamps();

            $table->foreign('vendor_credit_id')->references('vendor_credit_id')->on('vendor_credits')->onDelete('cascade');
            $table->foreign('bill_id')->references('bill_id')->on('bills')->onDelete('cascade');
            
            $table->index('vendor_credit_id');
            $table->index('bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_credit_applications');
        Schema::dropIfExists('vendor_credit_items');
        Schema::dropIfExists('vendor_credits');
    }
};
