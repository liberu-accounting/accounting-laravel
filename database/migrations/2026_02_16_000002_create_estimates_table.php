<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id('estimate_id');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('estimate_number')->unique();
            $table->date('estimate_date');
            $table->date('expiration_date')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, sent, viewed, accepted, declined, expired
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('estimate_date');
            $table->index('expiration_date');
            $table->index('status');
        });

        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('estimate_id')->constrained('estimates', 'estimate_id')->onDelete('cascade');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->timestamps();
            
            $table->index('estimate_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('estimate_items');
        Schema::dropIfExists('estimates');
    }
};
