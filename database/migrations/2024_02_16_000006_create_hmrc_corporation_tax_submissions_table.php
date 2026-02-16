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
        Schema::create('hmrc_corporation_tax_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('hmrc_submission_id')->nullable()->constrained('hmrc_submissions')->onDelete('set null');
            $table->string('accounting_period_start', 10)->comment('Accounting period start YYYY-MM-DD');
            $table->string('accounting_period_end', 10)->comment('Accounting period end YYYY-MM-DD');
            $table->decimal('turnover', 15, 2)->default(0);
            $table->decimal('total_profits', 15, 2)->default(0);
            $table->decimal('taxable_profits', 15, 2)->default(0);
            $table->decimal('corporation_tax_charged', 15, 2)->default(0);
            $table->decimal('marginal_relief', 15, 2)->default(0);
            $table->decimal('total_tax_payable', 15, 2)->default(0);
            $table->text('computation_data')->nullable()->comment('JSON detailed tax computation');
            $table->date('filing_due_date');
            $table->date('payment_due_date');
            $table->boolean('is_amended')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'accounting_period_start', 'accounting_period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmrc_corporation_tax_submissions');
    }
};
