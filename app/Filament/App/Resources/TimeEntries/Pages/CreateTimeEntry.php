<?php

namespace App\Filament\App\Resources\TimeEntries\Pages;

use App\Filament\App\Resources\TimeEntries\TimeEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;
}
