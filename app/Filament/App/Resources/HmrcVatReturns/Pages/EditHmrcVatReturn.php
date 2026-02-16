<?php

namespace App\Filament\App\Resources\HmrcVatReturns\Pages;

use App\Filament\App\Resources\HmrcVatReturns\HmrcVatReturnResource;
use Filament\Resources\Pages\EditRecord;

class EditHmrcVatReturn extends EditRecord
{
    protected static string $resource = HmrcVatReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
