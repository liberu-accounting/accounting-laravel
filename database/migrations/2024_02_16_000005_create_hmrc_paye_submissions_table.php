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
        Schema::create('hmrc_paye_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('hmrc_submission_id')->nullable()->constrained('hmrc_submissions')->onDelete('set null');
            $table->string('tax_year', 9)->comment('Tax year format: 2023-24');
            $table->integer('tax_month')->comment('Tax month 1-12');
            $table->date('payment_date');
            $table->string('fps_type')->default('regular')->comment('Full Payment Submission type: regular, final');
            $table->integer('employee_count')->default(0);
            $table->decimal('total_gross_pay', 15, 2)->default(0);
            $table->decimal('total_paye_tax', 15, 2)->default(0);
            $table->decimal('total_employee_ni', 15, 2)->default(0);
            $table->decimal('total_employer_ni', 15, 2)->default(0);
            $table->decimal('total_student_loan', 15, 2)->default(0);
            $table->text('employee_data')->nullable()->comment('JSON array of employee payment details');
            $table->boolean('late_reason_provided')->default(false);
            $table->text('late_reason')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'tax_year', 'tax_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmrc_paye_submissions');
    }
};
