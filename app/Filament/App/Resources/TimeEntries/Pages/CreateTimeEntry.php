<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TimeEntries\Pages;

use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    #[\Override]
    protected static string $resource = TimeEntryResource::class;
}
