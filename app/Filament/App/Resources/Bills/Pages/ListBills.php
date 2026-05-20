<?php

namespace App\Filament\App\Resources\Bills\Pages;

use App\Filament\App\Resources\Bills\BillResource;
use Filament\Resources\Pages\ListRecords;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;
}
