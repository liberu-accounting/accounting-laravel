<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\AssetResource\Pages\ListAssets;
use App\Filament\App\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\App\Resources\AssetResource\Pages\EditAsset;
use App\Filament\App\Resources\AssetResource\Pages\DepreciationSchedulePage;
use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Filament\App\Resources\AssetResource\Pages;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 4;
    protected static string | \UnitEnum | null $navigationGroup = 'Assets';
    protected static ?string $recordTitleAttribute = 'asset_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('asset_name')
                    ->required(),
                TextInput::make('asset_cost')
                    ->numeric()
                    ->required()
                    ->step('0.01')
                    ->min('0'),
                TextInput::make('useful_life_years')
                    ->numeric()
                    ->required()
                    ->min('0'),
                TextInput::make('salvage_value')
                    ->numeric()
                    ->required()
                    ->step('0.01')
                    ->min('0'),
                Select::make('depreciation_method')
                    ->options([
                        'straight_line' => 'Straight Line',
                        'reducing_balance' => 'Reducing Balance'
                    ])
                    ->required(),
                DatePicker::make('acquisition_date')
                    ->required()
            ]);
    }

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
                    ->url(fn (Asset $record) => route('filament.app.resources.assets.depreciation-schedule', $record))
                    ->button()
                    ->label('View Schedule')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

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