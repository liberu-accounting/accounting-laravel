<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixCustomerTaxRateForeignKey extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        // First, drop the foreign key if it exists
        Schema::table('customer_tax_rate', function (Blueprint $table) {
            // Get the database connection
            $connection = DB::connection();
            
            // Check if the foreign key exists
            $foreignKeys = $connection->getDoctrineSchemaManager()->listTableForeignKeys('customer_tax_rate');
            
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('customer_id', $foreignKey->getLocalColumns())) {
                    $table->dropForeign($foreignKey->getName());
                }
            }
        });

        // Get the column type from the customers table
        $customersColumnType = DB::getSchemaBuilder()->getColumnType('customers', 'customer_id');
        
        // Modify the customer_id column in the pivot table to match
        Schema::table('customer_tax_rate', function (Blueprint $table) use ($customersColumnType) {
            // Drop the column and recreate it with the correct type
            $table->dropColumn('customer_id');
        });
        
        Schema::table('customer_tax_rate', function (Blueprint $table) use ($customersColumnType) {
            if ($customersColumnType === 'bigint') {
                $table->unsignedBigInteger('customer_id')->first();
            } else if ($customersColumnType === 'integer') {
                $table->unsignedInteger('customer_id')->first();
            } else {
                // Default to bigInteger which is common for primary keys in Laravel
                $table->unsignedBigInteger('customer_id')->first();
            }
            
            // Add the foreign key constraint
            $table->foreign('customer_id')->references('customer_id')->on('customers');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_tax_rate', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['customer_id']);
        });
    }
}