

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_frequency')->nullable();
            $table->date('recurrence_start')->nullable();
            $table->date('recurrence_end')->nullable();
            $table->date('last_generated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn([
                'is_recurring',
                'recurrence_frequency',
                'recurrence_start',
                'recurrence_end',
                'last_generated',
            ]);
        });
    }
};
