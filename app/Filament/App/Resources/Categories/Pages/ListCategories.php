<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Categories\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    #[\Override]
    protected static string $resource = CategoryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
