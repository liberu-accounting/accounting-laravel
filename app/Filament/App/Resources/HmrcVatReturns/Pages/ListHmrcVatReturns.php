<?php

namespace App\Filament\App\Resources\HmrcVatReturns\Pages;

use App\Filament\App\Resources\HmrcVatReturns\HmrcVatReturnResource;
use Filament\Resources\Pages\ListRecords;

class ListHmrcVatReturns extends ListRecords
{
    protected static string $resource = HmrcVatReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
