<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Companies\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Companies\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    #[\Override]
    protected static string $resource = CompanyResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
