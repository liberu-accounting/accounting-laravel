

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id('inventory_transaction_id');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->enum('transaction_type', ['purchase', 'sale', 'adjustment']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
    }
};