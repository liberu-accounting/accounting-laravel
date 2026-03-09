<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
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
