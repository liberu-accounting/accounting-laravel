<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Activation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Filament\App\Resources\ActivationResource\Pages;
use App\Filament\App\Resources\ActivationResource\RelationManagers;

class ActivationResource extends Resource
{
    protected static ?string $model = Activation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                BelongsToSelect::make('user_id')
                    ->relationship('user', 'email')
                    ->preload(),
                TextInput::make('token'),
                TextInput::make('ip_address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('token')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->sortable(),
                BelongsTo::make('user')
                    ->label('User')
                    ->relationship('user', 'email')
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
            'index' => Pages\ListActivations::route('/'),
            'create' => Pages\CreateActivation::route('/create'),
            'edit' => Pages\EditActivation::route('/{record}/edit'),
        ];
    }
}
