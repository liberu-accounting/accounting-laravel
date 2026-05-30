<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Invoices\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Invoices\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    #[\Override]
    protected static string $resource = InvoiceResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
