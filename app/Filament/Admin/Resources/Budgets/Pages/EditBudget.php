<?php

namespace App\Filament\Admin\Resources\Budgets\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\Budgets\BudgetResource;
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
