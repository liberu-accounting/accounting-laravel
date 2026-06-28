<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['accounts', 'bills', 'payments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'xero_id')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->string('xero_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'xero_id')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->dropColumn('xero_id');
                });
            }
        }
    }
};
