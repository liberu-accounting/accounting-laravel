<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Transactions\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Transactions\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    #[\Override]
    protected static string $resource = TransactionResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
