<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Categories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    #[\Override]
    protected static string $resource = CategoryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
