<?php

namespace App\Filament\App\Resources\TimeEntries\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
