<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id('bill_id');
            $table->foreignId('vendor_id')->constrained('vendors', 'vendor_id')->onDelete('cascade');
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, open, paid, void, overdue
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders', 'purchase_order_id')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('vendor_id');
            $table->index('bill_date');
            $table->index('due_date');
            $table->index('status');
            $table->index('payment_status');
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('bill_id')->constrained('bills', 'bill_id')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->timestamps();
            
            $table->index('bill_id');
            $table->index('account_id');
        });

        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('bill_id')->constrained('bills', 'bill_id')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable(); // check, bank_transfer, credit_card, cash
            $table->string('reference_number')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_connections', 'id')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('bill_id');
            $table->index('payment_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bill_payments');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
