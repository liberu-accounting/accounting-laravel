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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('national_insurance_number', 9)->nullable()->after('tax_id')->comment('UK National Insurance Number');
            $table->string('starter_declaration', 50)->nullable()->after('national_insurance_number')->comment('PAYE Starter Declaration A, B, or C');
            $table->date('p45_issue_date')->nullable()->after('starter_declaration')->comment('Date P45 was issued');
            $table->boolean('has_student_loan')->default(false)->after('p45_issue_date')->comment('Student loan deduction required');
            $table->string('student_loan_plan', 10)->nullable()->after('has_student_loan')->comment('Student loan plan: plan_1, plan_2, plan_4, postgraduate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'national_insurance_number',
                'starter_declaration',
                'p45_issue_date',
                'has_student_loan',
                'student_loan_plan',
            ]);
        });
    }
};
