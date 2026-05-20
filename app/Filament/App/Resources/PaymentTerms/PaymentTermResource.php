<?php

namespace App\Filament\App\Resources\PaymentTerms;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\PaymentTerms\Pages\ListPaymentTerms;
use App\Filament\App\Resources\PaymentTerms\Pages\CreatePaymentTerm;
use App\Filament\App\Resources\PaymentTerms\Pages\EditPaymentTerm;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PaymentTerm;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\PaymentTermResource\Pages;
use App\Filament\App\Resources\PaymentTermResource\RelationManagers;

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTerms::route('/'),
            'create' => CreatePaymentTerm::route('/create'),
            'edit' => EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
