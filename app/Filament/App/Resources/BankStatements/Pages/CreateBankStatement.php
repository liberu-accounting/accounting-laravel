<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BankStatements\Pages;

use App\Filament\App\Resources\BankStatements\BankStatementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankStatement extends CreateRecord
{
    #[\Override]
    protected static string $resource = BankStatementResource::class;
}
