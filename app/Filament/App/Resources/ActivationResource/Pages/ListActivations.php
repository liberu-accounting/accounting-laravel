<?php

namespace App\Filament\App\Resources\ActivationResource\Pages;

use App\Filament\App\Resources\ActivationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivations extends ListRecords
{
    protected static string $resource = ActivationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
