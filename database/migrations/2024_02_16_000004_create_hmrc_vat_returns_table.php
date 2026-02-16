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
        Schema::create('hmrc_vat_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('hmrc_submission_id')->nullable()->constrained('hmrc_submissions')->onDelete('set null');
            $table->string('period_key')->comment('HMRC VAT period key');
            $table->date('period_from');
            $table->date('period_to');
            $table->date('due_date');
            $table->decimal('vat_due_sales', 15, 2)->default(0)->comment('Box 1: VAT due on sales');
            $table->decimal('vat_due_acquisitions', 15, 2)->default(0)->comment('Box 2: VAT due on EC acquisitions');
            $table->decimal('total_vat_due', 15, 2)->default(0)->comment('Box 3: Total VAT due');
            $table->decimal('vat_reclaimed', 15, 2)->default(0)->comment('Box 4: VAT reclaimed on purchases');
            $table->decimal('net_vat_due', 15, 2)->default(0)->comment('Box 5: Net VAT to pay/reclaim');
            $table->decimal('total_value_sales', 15, 2)->default(0)->comment('Box 6: Total sales excluding VAT');
            $table->decimal('total_value_purchases', 15, 2)->default(0)->comment('Box 7: Total purchases excluding VAT');
            $table->decimal('total_value_goods_supplied', 15, 2)->default(0)->comment('Box 8: Total EC goods supplied');
            $table->decimal('total_acquisitions', 15, 2)->default(0)->comment('Box 9: Total EC acquisitions');
            $table->boolean('finalised')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'period_from', 'period_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmrc_vat_returns');
    }
};
