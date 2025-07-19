<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixCustomerTaxRateForeignKey extends Migration
{
    public function up()
    {
        Schema::table('customer_tax_rate', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        $customersColumnType = DB::getSchemaBuilder()->getColumnType('customers', 'customer_id');

        Schema::table('customer_tax_rate', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('customer_tax_rate', function (Blueprint $table) use ($customersColumnType) {
            if ($customersColumnType === 'bigint') {
                $table->unsignedBigInteger('customer_id')->first();
            } elseif ($customersColumnType === 'integer') {
                $table->unsignedInteger('customer_id')->first();
            } else {
                $table->unsignedBigInteger('customer_id')->first();
            }
            $table->foreign('customer_id')->references('customer_id')->on('customers');
        });
    }

    public function down()
    {
        Schema::table('customer_tax_rate', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
}