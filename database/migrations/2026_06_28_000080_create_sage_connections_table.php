<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sage_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('business_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'sage_id')) {
                $table->string('sage_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sage_connections');
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('sage_id');
        });
    }
};
