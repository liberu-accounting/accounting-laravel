<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Suppliers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    #[\Override]
    protected static string $resource = SupplierResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
