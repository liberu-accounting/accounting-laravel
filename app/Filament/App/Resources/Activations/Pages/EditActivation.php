<?php

namespace App\Filament\App\Resources\Activations\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Activations\ActivationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivation extends EditRecord
{
    protected static string $resource = ActivationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}