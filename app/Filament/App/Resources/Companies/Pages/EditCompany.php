<?php

namespace App\Filament\App\Resources\CompanyResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}