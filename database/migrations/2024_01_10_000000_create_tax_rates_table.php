

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id('tax_rate_id');
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_tax_rate', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers', 'customer_id');
            $table->foreignId('tax_rate_id')->constrained('tax_rates', 'tax_rate_id');
            $table->primary(['customer_id', 'tax_rate_id']);
        });

        Schema::create('supplier_tax_rate', function (Blueprint $table) {
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id');
            $table->foreignId('tax_rate_id')->constrained('tax_rates', 'tax_rate_id');
            $table->primary(['supplier_id', 'tax_rate_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_tax_rate');
        Schema::dropIfExists('customer_tax_rate');
        Schema::dropIfExists('tax_rates');
    }
};