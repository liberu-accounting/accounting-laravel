<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_tax_rate')) {
            Schema::table('customer_tax_rate', function (Blueprint $table) {
                // First check if the foreign key exists
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('customer_tax_rate');
                
                $foreignKeyExists = collect($foreignKeys)
                    ->contains(function ($foreignKey) {
                        return $foreignKey->getName() === 'customer_tax_rate_customer_id_foreign';
                    });

                if ($foreignKeyExists) {
                    $table->dropForeign(['customer_id']);
                }
                
                // Add the new foreign key constraint
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('customer_tax_rate')) {
            Schema::table('customer_tax_rate', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers');
            });
        }
    }
};