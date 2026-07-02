<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose models reference project_id / a project() relation, plus
     * transactions (Project::transactions() hasMany + Budget::getActualAmount()).
     *
     * @var list<string>
     */
    protected array $tables = [
        'budgets',
        'expenses',
        'estimates',
        'invoices',
        'time_entries',
        'transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'project_id')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'project_id')) {
                Schema::table($table, function (Blueprint $table): void {
                    $table->dropForeign(['project_id']);
                    $table->dropColumn('project_id');
                });
            }
        }
    }
};
