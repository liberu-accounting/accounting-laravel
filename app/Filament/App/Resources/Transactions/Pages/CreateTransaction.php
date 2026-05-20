<?php

namespace App\Filament\App\Resources\Transactions\Pages;

use App\Filament\App\Resources\Transactions\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
