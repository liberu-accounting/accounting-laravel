<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\AssetResource\Pages;
use App\Filament\App\Resources\AssetResource\RelationManagers;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('calculate_depreciation')
                    ->action(fn (Asset $record) => $record->calculateDepreciation())
                    ->button()
                    ->label('Calculate Depreciation'),
                Action::make('view_schedule')
                    ->url(fn (Asset $record) => route('filament.app.resources.assets.depreciation-schedule', $record))
                    ->button()
                    ->label('View Schedule')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
