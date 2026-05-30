<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CreditMemos\Pages;

use App\Filament\App\Resources\CreditMemos\CreditMemoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditMemo extends CreateRecord
{
    #[\Override]
    protected static string $resource = CreditMemoResource::class;
}
