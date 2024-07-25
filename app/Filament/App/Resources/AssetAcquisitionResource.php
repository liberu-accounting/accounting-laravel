<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\AssetAcquisition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\AssetAcquisitionResource\Pages;
use App\Filament\App\Resources\AssetAcquisitionResource\RelationManagers;

class AssetAcquisitionResource extends Resource
{
    protected static ?string $model = AssetAcquisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('acquisition_date')
                    ->numeric(),
                TextInput::make('acquisition_price')
                    ->numeric()
                    ->step('0.01')
                    ->min('0'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('acquisition_date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acquisition_price')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset.asset_name')
                    ->label('Asset Name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAssetAcquisitions::route('/'),
            'create' => Pages\CreateAssetAcquisition::route('/create'),
            'edit' => Pages\EditAssetAcquisition::route('/{record}/edit'),
        ];
    }
}
