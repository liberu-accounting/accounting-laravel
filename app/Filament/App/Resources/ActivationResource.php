<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\ActivationResource\Pages\ListActivations;
use App\Filament\App\Resources\ActivationResource\Pages\CreateActivation;
use App\Filament\App\Resources\ActivationResource\Pages\EditActivation;
use Filament\Forms;
use Filament\Tables;
use App\Models\Activation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use App\Filament\App\Resources\ActivationResource\Pages;

class ActivationResource extends Resource
{
    protected static ?string $model = Activation::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->preload()
                    ->searchable(),
                TextInput::make('token')
                    ->required(),
                TextInput::make('ip_address')
                    ->required(),
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
                TextColumn::make('user.email')
                    ->label('User')
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
            'index' => ListActivations::route('/'),
            'create' => CreateActivation::route('/create'),
            'edit' => EditActivation::route('/{record}/edit'),
        ];
    }
}