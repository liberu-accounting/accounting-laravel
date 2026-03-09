<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
