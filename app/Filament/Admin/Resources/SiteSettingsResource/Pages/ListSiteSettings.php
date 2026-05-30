<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SiteSettingsResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\SiteSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    #[\Override]
    protected static string $resource = SiteSettingsResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
