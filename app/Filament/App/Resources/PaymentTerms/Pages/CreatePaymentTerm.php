<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PaymentTerms\Pages;

use App\Filament\App\Resources\PaymentTerms\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentTerm extends CreateRecord
{
    #[\Override]
    protected static string $resource = PaymentTermResource::class;
}
