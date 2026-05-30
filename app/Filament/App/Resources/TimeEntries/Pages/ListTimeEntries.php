<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TimeEntries\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    #[\Override]
    protected static string $resource = TimeEntryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
