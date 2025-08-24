<?php

namespace App\Filament\Resources\Budgets\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Budgets\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
