<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\AssetAcquisitionResource\Pages\ListAssetAcquisitions;
use App\Filament\App\Resources\AssetAcquisitionResource\Pages\CreateAssetAcquisition;
use App\Filament\App\Resources\AssetAcquisitionResource\Pages\EditAssetAcquisition;
use Filament\Forms;
use Filament\Tables;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ->recordActions([
                EditAction::make(),
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
            'index' => ListAssetAcquisitions::route('/'),
            'create' => CreateAssetAcquisition::route('/create'),
            'edit' => EditAssetAcquisition::route('/{record}/edit'),
        ];
    }
}
