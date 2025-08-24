<?php

namespace App\Filament\App\Resources\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
