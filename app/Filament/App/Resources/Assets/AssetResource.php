<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Assets;

use App\Filament\App\Resources\Assets\Pages\CreateAsset;
use App\Filament\App\Resources\Assets\Pages\DepreciationSchedulePage;
use App\Filament\App\Resources\Assets\Pages\EditAsset;
use App\Filament\App\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Modules\FixedAssets\FixedAssetsModule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    #[\Override]
    protected static ?string $model = Asset::class;

    // Gated by the FixedAssets module: disabling it removes this resource.
    #[\Override]
    public static function canAccess(): bool
    {
        return FixedAssetsModule::isActive();
    }

    #[\Override]
    public static function shouldRegisterNavigation(): bool
    {
        return FixedAssetsModule::isActive();
    }

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    #[\Override]
    protected static ?int $navigationSort = 4;

    #[\Override]
    protected static string|\UnitEnum|null $navigationGroup = 'Assets';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'asset_name';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asset Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('asset_name')
                            ->required(),
                        TextInput::make('asset_cost')
                            ->numeric()
                            ->required()
                            ->step('0.01')
                            ->minValue('0'),
                        TextInput::make('useful_life_years')
                            ->numeric()
                            ->required()
                            ->minValue('0'),
                        TextInput::make('salvage_value')
                            ->numeric()
                            ->required()
                            ->step('0.01')
                            ->minValue('0'),
                        Select::make('depreciation_method')
                            ->options([
                                'straight_line' => 'Straight Line',
                                'reducing_balance' => 'Reducing Balance',
                            ])
                            ->required(),
                        DatePicker::make('acquisition_date')
                            ->required(),
                    ]),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset_cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('useful_life_years')
                    ->sortable(),
                TextColumn::make('depreciation_method')
                    ->sortable(),
                TextColumn::make('salvage_value')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('calculate_depreciation')
                    ->action(fn (Asset $record) => $record->calculateDepreciation())
                    ->button()
                    ->label('Calculate Depreciation'),
                Action::make('view_schedule')
                    ->url(fn (Asset $record): string => route('filament.app.resources.assets.depreciation-schedule', $record))
                    ->button()
                    ->label('View Schedule'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
            'depreciation-schedule' => DepreciationSchedulePage::route('/{record}/depreciation-schedule'),
        ];
    }
}
