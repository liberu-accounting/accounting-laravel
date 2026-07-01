<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->index();
            $table->string('approvable_type');
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->json('steps');
            $table->unsignedInteger('deadline_days')->nullable();
            $table->string('fallback_role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'approvable_type', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('approval_rules'); }
};
