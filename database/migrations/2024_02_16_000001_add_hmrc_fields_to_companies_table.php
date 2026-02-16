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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('hmrc_utr', 10)->nullable()->after('phone')->comment('HMRC Unique Taxpayer Reference');
            $table->string('hmrc_paye_reference', 20)->nullable()->after('hmrc_utr')->comment('HMRC PAYE Reference');
            $table->string('hmrc_vat_number', 12)->nullable()->after('hmrc_paye_reference')->comment('VAT Registration Number');
            $table->string('hmrc_accounts_office_reference', 20)->nullable()->after('hmrc_vat_number')->comment('Accounts Office Reference');
            $table->string('hmrc_corporation_tax_utr', 10)->nullable()->after('hmrc_accounts_office_reference')->comment('Corporation Tax UTR');
            $table->string('vat_scheme', 50)->nullable()->after('hmrc_corporation_tax_utr')->comment('VAT Scheme: standard, flat_rate, cash_accounting');
            $table->string('vat_period', 20)->nullable()->after('vat_scheme')->comment('VAT return period: monthly, quarterly, annually');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'hmrc_utr',
                'hmrc_paye_reference',
                'hmrc_vat_number',
                'hmrc_accounts_office_reference',
                'hmrc_corporation_tax_utr',
                'vat_scheme',
                'vat_period',
            ]);
        });
    }
};
