<?php

namespace App\Filament\App\Resources\CreditMemos\Pages;

use App\Filament\App\Resources\CreditMemos\CreditMemoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditMemo extends CreateRecord
{
    protected static string $resource = CreditMemoResource::class;
}
