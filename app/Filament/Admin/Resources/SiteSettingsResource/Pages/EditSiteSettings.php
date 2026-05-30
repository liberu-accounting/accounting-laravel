<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SiteSettingsResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\SiteSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteSettings extends EditRecord
{
    #[\Override]
    protected static string $resource = SiteSettingsResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
