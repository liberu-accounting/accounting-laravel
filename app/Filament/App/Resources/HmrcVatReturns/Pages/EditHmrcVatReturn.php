<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HmrcVatReturns\Pages;

use App\Filament\App\Resources\HmrcVatReturns\HmrcVatReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHmrcVatReturn extends EditRecord
{
    #[\Override]
    protected static string $resource = HmrcVatReturnResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
