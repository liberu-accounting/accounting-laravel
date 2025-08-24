<?php

namespace App\Filament\App\Resources\ReminderSettingResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\ReminderSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReminderSetting extends EditRecord
{
    protected static string $resource = ReminderSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
