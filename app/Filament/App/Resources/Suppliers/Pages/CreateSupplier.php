<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Suppliers\Pages;

use App\Filament\App\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    #[\Override]
    protected static string $resource = SupplierResource::class;
}
