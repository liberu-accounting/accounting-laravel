<?php

namespace App\Filament\App\Resources\Activations\Pages;

use App\Filament\App\Resources\Activations\ActivationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateActivation extends CreateRecord
{
    protected static string $resource = ActivationResource::class;
}