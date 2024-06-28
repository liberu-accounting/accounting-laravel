<?php

namespace App\Filament\Admin\Resources\ActivationResource\Pages;

use App\Filament\Admin\Resources\ActivationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivation extends EditRecord
{
    protected static string $resource = ActivationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
