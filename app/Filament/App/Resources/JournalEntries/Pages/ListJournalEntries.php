<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\JournalEntries\Pages;

use App\Filament\App\Resources\JournalEntries\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    #[\Override]
    protected static string $resource = JournalEntryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
