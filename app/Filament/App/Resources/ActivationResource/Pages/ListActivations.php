<?php

namespace App\Filament\Admin\Resources\ActivationResource\Pages;

use App\Filament\Admin\Resources\ActivationResource;
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
