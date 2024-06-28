<?php

namespace App\Filament\Admin\Resources\PaymentTermResource\Pages;

use App\Filament\Admin\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentTerm extends CreateRecord
{
    protected static string $resource = PaymentTermResource::class;
}
