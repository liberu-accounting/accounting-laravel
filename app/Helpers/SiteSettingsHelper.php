<?php

namespace App\Helpers;

use App\Models\SiteSettings;

class SiteSettingsHelper
{
    public static function get($key, $default = null)
    {
        return SiteSettings::get($key) ?? $default;
    }
}