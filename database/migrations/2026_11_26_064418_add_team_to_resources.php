<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tables = [
        'account_templates',
        'accounts',
        'asset_acquisitions',
        'assets',
        'audit_logs',
        'bank_account_balances',
        'bank_connections',
        'bank_feed_transactions',
        'bank_statements',
        'bills',
        'budgets',
        'categories',
        'companies',
        'connected_accounts',
        'credit_memos',
        'currencies',
        'customers',
        'delayed_charges',
        'depreciation_calculations',
        'domains',
        'employees',
        'estimates',
        'expenses',
        'hmrc_corporation_tax_submissions',
        'hmrc_paye_submissions',
        'hmrc_submissions',
        'hmrc_vat_returns',
        'inventory_cost_layers',
        'inventory_items',
        'inventory_transactions',
        'invoices',
        'journal_entries',
        'journal_entry_lines',
        'menus',
        'payment_terms',
        'payments',
        'payrolls',
        'purchase_orders',
        'refund_receipts',
        'reminder_settings',
        'sales_receipts',
        'settings',
        'suppliers',
        'tax_forms',
        'tax_rates',
        'tenants',
        'time_entries',
        'transactions',
        'vendors',
        'vendor_credits',
    ];
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade')->default(1);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['team_id']);
                    $table->dropColumn('team_id');
                });
            }
        }
    }
};
