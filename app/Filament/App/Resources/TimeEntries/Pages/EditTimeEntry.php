<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TimeEntries\Pages;

use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    #[\Override]
    protected static string $resource = TimeEntryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
