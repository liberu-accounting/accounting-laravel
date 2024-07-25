<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\SupplierResource\Pages;
use App\Filament\App\Resources\SupplierResource\RelationManagers;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                BelongsToSelect::make('payment_term_id')
                    ->relationship('paymentTerm', 'payment_term_name')
                    ->label('Payment Term'),
                TextInput::make('supplier_first_name')
                    ->label('First Name'),
                TextInput::make('supplier_last_name')
                    ->label('Last Name'),
                TextInput::make('supplier_email')
                    ->label('Email'),
                TextInput::make('supplier_address')
                    ->label('Address'),
                TextInput::make('supplier_phone_number')
                    ->numeric()
                    ->label('Phone Number'),
                TextInput::make('supplier_limit_credit')
                    ->numeric()
                    ->label('Limit Credit'),
                TextInput::make('supplier_tin')
                    ->numeric()
                    ->label('TIN'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_term_id')
                    ->label('Payment Term')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_address')
                    ->label('Address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_phone_number')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_limit_credit')
                    ->label('Limit Credit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_tin')
                    ->label('TIN')
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
