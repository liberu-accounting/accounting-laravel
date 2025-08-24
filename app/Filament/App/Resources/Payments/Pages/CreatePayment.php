<?php

namespace App\Filament\App\Resources\PaymentResource\Pages;

use App\Filament\App\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
