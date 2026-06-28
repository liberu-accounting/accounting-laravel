

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->date('acquisition_date')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn([
                'depreciation_method',
                'salvage_value',
                'acquisition_date',
                'is_active',
            ]);
        });
    }
};
