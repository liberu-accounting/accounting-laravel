<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
