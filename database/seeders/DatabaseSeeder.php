<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SiteSettingsSeeder::class,
            // PermissionsSeeder::class,
            MenuSeeder::class,
            RolesSeeder::class,
            TeamSeeder::class,
            UserSeeder::class,
        ]);
    }
}
