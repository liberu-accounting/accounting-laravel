

<?php

namespace App\Filament\App\Resources\ReminderSettingResource\Pages;

use App\Filament\App\Resources\ReminderSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReminderSetting extends EditRecord
{
    protected static string $resource = ReminderSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}