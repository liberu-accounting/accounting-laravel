<?php

namespace App\Filament\Resources\Expenses\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
