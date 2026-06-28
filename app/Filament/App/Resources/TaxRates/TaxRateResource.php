<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxRates;

use App\Filament\App\Resources\TaxRateResource\Pages;
use App\Filament\App\Resources\TaxRates\Pages\CreateTaxRate;
use App\Filament\App\Resources\TaxRates\Pages\EditTaxRate;
use App\Filament\App\Resources\TaxRates\Pages\ListTaxRates;
use App\Models\TaxRate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TaxRateResource extends Resource
{
    #[\Override]
    protected static ?string $model = TaxRate::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%'),
                Textarea::make('description')
                    ->maxLength(65535),
                Toggle::make('is_compound')
                    ->label('Compound Tax')
                    ->helperText('Apply this tax after other taxes'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('rate')
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTaxRates::route('/'),
            'create' => CreateTaxRate::route('/create'),
            'edit' => EditTaxRate::route('/{record}/edit'),
        ];
    }
}
