<?php

namespace App\Filament\App\Resources\TimeEntries\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
