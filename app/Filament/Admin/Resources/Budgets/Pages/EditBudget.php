<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Budgets\Pages;

use App\Filament\Admin\Resources\Budgets\BudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    #[\Override]
    protected static string $resource = BudgetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
