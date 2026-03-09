<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
