<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Suppliers;

use App\Filament\App\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\App\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\App\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    #[\Override]
    protected static ?string $model = Supplier::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    #[\Override]
    protected static ?int $navigationSort = 5;

    #[\Override]
    protected static string|\UnitEnum|null $navigationGroup = 'Vendors';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'supplier_name';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
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

    #[\Override]
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
