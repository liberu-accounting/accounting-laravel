<?php

namespace App\Filament\App\Resources\BankStatements\Pages;

use App\Filament\App\Resources\BankStatements\BankStatementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;
}
