<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hmrc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('submission_type')->comment('Type: vat_return, paye_rti, corporation_tax');
            $table->string('tax_period_from', 10)->comment('Tax period start date YYYY-MM-DD');
            $table->string('tax_period_to', 10)->comment('Tax period end date YYYY-MM-DD');
            $table->string('status')->default('draft')->comment('Status: draft, pending, submitted, accepted, rejected');
            $table->string('hmrc_reference')->nullable()->comment('HMRC submission reference/receipt ID');
            $table->text('submission_data')->nullable()->comment('JSON data submitted to HMRC');
            $table->text('response_data')->nullable()->comment('JSON response from HMRC');
            $table->text('error_message')->nullable()->comment('Error details if submission failed');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'submission_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmrc_submissions');
    }
};
