

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->integer('current_quantity')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->enum('valuation_method', ['fifo', 'lifo', 'average'])->default('fifo');
            $table->decimal('average_cost', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_items');
    }
};
