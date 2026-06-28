<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Invoices\Pages;

use App\Filament\App\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    #[\Override]
    protected static string $resource = InvoiceResource::class;
}
