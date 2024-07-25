<?php

namespace App\Filament\App\Resources\TransactionResource\Pages;

use App\Filament\App\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
