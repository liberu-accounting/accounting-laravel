

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tax_forms', function (Blueprint $table) {
            $table->id('tax_form_id');
            $table->string('form_type'); // e.g., '1099-MISC'
            $table->foreignId('customer_id')->constrained('customers', 'customer_id');
            $table->year('tax_year');
            $table->decimal('total_payments', 12, 2);
            $table->decimal('total_tax_withheld', 12, 2);
            $table->string('status'); // draft, generated, submitted
            $table->json('form_data'); // Store additional form-specific data
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tax_forms');
    }
};