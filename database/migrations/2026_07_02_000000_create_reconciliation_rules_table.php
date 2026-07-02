<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('match_field');            // description | amount | reference
            $table->string('match_operator');         // contains | equals | between
            $table->string('match_value');
            $table->string('match_value_secondary')->nullable(); // upper bound for `between`
            $table->foreignId('action_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->integer('priority')->default(0);  // lower runs first
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_rules');
    }
};
