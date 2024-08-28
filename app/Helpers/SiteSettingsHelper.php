<?php

namespace App\Helpers;

use App\Models\SiteSettings;

class SiteSettingsHelper
{
    public static function get($default = null)
    {
        return SiteSettings::first() ?? $default;
    }
}