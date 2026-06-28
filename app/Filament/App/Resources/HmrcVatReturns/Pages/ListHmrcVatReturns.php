<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\HmrcVatReturns\Pages;

use App\Filament\App\Resources\HmrcVatReturns\HmrcVatReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHmrcVatReturns extends ListRecords
{
    #[\Override]
    protected static string $resource = HmrcVatReturnResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
