<?php

namespace App\Filament\App\Resources\SupplierResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
