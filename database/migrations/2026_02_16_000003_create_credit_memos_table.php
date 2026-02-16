<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id('credit_memo_id');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('credit_memo_number')->unique();
            $table->date('credit_memo_date');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_applied', 15, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, open, applied, void
            $table->string('reason')->nullable(); // product_return, billing_error, discount, other
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('invoice_id');
            $table->index('credit_memo_date');
            $table->index('status');
        });

        Schema::create('credit_memo_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('credit_memo_id')->constrained('credit_memos', 'credit_memo_id')->onDelete('cascade');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->timestamps();
            
            $table->index('credit_memo_id');
        });

        Schema::create('credit_memo_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('credit_memo_id')->constrained('credit_memos', 'credit_memo_id')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->decimal('amount_applied', 15, 2);
            $table->date('application_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('credit_memo_id');
            $table->index('invoice_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_memo_applications');
        Schema::dropIfExists('credit_memo_items');
        Schema::dropIfExists('credit_memos');
    }
};
