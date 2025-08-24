<?php

namespace App\Filament\App\Resources\PaymentTerms\Pages;

use App\Filament\App\Resources\PaymentTerms\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentTerm extends CreateRecord
{
    protected static string $resource = PaymentTermResource::class;
}
