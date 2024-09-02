<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Intelrx\Sitesettings\SiteConfig;


class SiteSettingsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'name' => config('app.name', 'Liberu '),
            'currency' => '£',
            'default_language' => 'en',
            'address' => '123  St, London, UK',
            'country' => 'United Kingdom',
            'email' => 'info@liberurealestate.com',
            'phone_01' => '+44 123 456 7890',
            'phone_02' => '+44 123 456 7890',
            'phone_03' => '+44 123 456 7890',
            'phone_04' => '+44 123 456 7890',
            'facebook' => 'https://facebook.com/liberusoftware',
            'twitter' => 'https://twitter.com/liberusoftware',
            'github' => 'https://Github.com/liberusoftware',
            'youtube' => 'https://YouTube.com/@liberusoftware',
        ];

        foreach ($data as $key => $value) {
            SiteConfig::store($key, $value);
        }
    }
}