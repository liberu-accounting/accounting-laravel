<?php

namespace App\Filament\App\Resources\AssetResource\Pages;

use Filament\Actions\Action;
use App\Filament\App\Resources\AssetResource;
use App\Models\Asset;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class DepreciationSchedulePage extends Page
{
    protected static string $resource = AssetResource::class;

    protected string $view = 'filament.app.resources.asset-resource.pages.depreciation-schedule-page';

    public Asset $record;

    public function mount(Asset $record): void
    {
        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->url(fn () => AssetResource::getUrl())
                ->label('Back to Assets')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}