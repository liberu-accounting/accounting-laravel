<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReminderSettings\Pages;

use App\Filament\App\Resources\ReminderSettings\ReminderSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReminderSetting extends CreateRecord
{
    #[\Override]
    protected static string $resource = ReminderSettingResource::class;
}
