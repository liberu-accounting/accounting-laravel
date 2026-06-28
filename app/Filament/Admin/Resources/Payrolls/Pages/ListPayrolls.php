<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    #[\Override]
    protected static string $resource = PayrollResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
