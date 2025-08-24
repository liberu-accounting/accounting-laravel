<?php

namespace App\Filament\App\Resources\CategoryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
