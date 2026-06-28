<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PaymentTerms;

use App\Filament\App\Resources\PaymentTermResource\Pages;
use App\Filament\App\Resources\PaymentTermResource\RelationManagers;
use App\Filament\App\Resources\PaymentTerms\Pages\CreatePaymentTerm;
use App\Filament\App\Resources\PaymentTerms\Pages\EditPaymentTerm;
use App\Filament\App\Resources\PaymentTerms\Pages\ListPaymentTerms;
use App\Models\PaymentTerm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentTermResource extends Resource
{
    #[\Override]
    protected static ?string $model = PaymentTerm::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payment_term_name')
                    ->label('Name'),
                Textarea::make('payment_term_description')
                    ->label('Description'),
                TextInput::make('payment_term_number_of_days')
                    ->numeric()
                    ->label('Number of Days'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_term_name')
                    ->label('Name')->searchable()
                    ->sortable(),
                TextColumn::make('payment_term_description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_term_number_of_days')
                    ->label('Number of Days')
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
            'index' => ListPaymentTerms::route('/'),
            'create' => CreatePaymentTerm::route('/create'),
            'edit' => EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
