

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_feed_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->onDelete('cascade');
            $table->foreignId('bank_connection_id')->constrained()->onDelete('cascade');
            $table->json('raw_data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_feed_transactions');
    }
};