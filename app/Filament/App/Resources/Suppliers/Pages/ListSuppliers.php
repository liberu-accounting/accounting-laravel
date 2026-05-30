<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Suppliers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    #[\Override]
    protected static string $resource = SupplierResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
