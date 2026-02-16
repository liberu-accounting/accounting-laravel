<?php

namespace App\Filament\App\Resources\CreditMemos\Pages;

use App\Filament\App\Resources\CreditMemos\CreditMemoResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditMemos extends ListRecords
{
    protected static string $resource = CreditMemoResource::class;
}
