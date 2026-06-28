<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            // PK is asset_id to match App\Models\Asset::$primaryKey and the Filament AssetResource.
            $table->id('asset_id');
            $table->string('asset_name');
            $table->decimal('asset_cost', 15, 2);
            $table->integer('useful_life_years');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
}
