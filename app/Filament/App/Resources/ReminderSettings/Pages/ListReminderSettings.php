<?php

namespace App\Filament\App\Resources\ReminderSettingResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\ReminderSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReminderSettings extends ListRecords
{
    protected static string $resource = ReminderSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
