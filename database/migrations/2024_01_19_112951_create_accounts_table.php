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
        Schema::create('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->foreignId('user_id')->constrained('users')->index()->name('users_id_foreign');
            $table->integer('account_number')->unique();
            $table->string('account_name')->unique();
            $table->string('account_type');
            $table->decimal('balance', 10, 2)->default(0);
            $table->foreignId('parent_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->string('industry_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
