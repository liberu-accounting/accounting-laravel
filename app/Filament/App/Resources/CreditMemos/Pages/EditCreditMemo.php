<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\CreditMemos\Pages;

use App\Filament\App\Resources\CreditMemos\CreditMemoResource;
use Filament\Resources\Pages\EditRecord;

class EditCreditMemo extends EditRecord
{
    #[\Override]
    protected static string $resource = CreditMemoResource::class;
}
