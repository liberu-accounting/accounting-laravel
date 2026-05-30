<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReminderSettings\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\ReminderSettings\ReminderSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReminderSettings extends ListRecords
{
    #[\Override]
    protected static string $resource = ReminderSettingResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
