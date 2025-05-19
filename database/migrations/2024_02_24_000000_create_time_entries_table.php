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
        
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->text('description');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 10, 2);

$table->foreignId('customer_id')->constrained()->onDelete('cascade')
    $table->foreignId('invoice_id')->constrained()->onDelete('cascade')

            
            $table->timestamps();

          });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
