<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Payments\Pages;

use App\Filament\App\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    #[\Override]
    protected static string $resource = PaymentResource::class;
}
