<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\SupplierResource\Pages\ListSuppliers;
use App\Filament\App\Resources\SupplierResource\Pages\CreateSupplier;
use App\Filament\App\Resources\SupplierResource\Pages\EditSupplier;
use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Filament\App\Resources\SupplierResource\Pages;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?string $recordTitleAttribute = 'supplier_name';

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('payment_term_id')
                    ->relationship('paymentTerm', 'payment_term_name')
                    ->searchable()
                    ->preload()
                    ->required(),
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}