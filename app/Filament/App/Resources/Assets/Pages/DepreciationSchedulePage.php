<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Assets\Pages;

use Filament\Actions\Action;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Models\Asset;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class DepreciationSchedulePage extends Page
{
    #[\Override]
    protected static string $resource = AssetResource::class;

    #[\Override]
    protected string $view = 'filament.app.resources.asset-resource.pages.depreciation-schedule-page';

    public Asset $record;

    public function mount(Asset $record): void
    {
        $this->record = $record;
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->url(fn (): string => AssetResource::getUrl())
                ->label('Back to Assets')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
