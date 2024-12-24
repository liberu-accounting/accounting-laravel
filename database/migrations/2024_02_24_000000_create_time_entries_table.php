

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('invoice_id')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->text('description');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};