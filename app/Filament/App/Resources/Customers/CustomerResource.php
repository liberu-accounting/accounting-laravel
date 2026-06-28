<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Customers;

use App\Filament\App\Resources\CustomerResource\Pages;
use App\Filament\App\Resources\CustomerResource\RelationManagers;
use App\Filament\App\Resources\Customers\Pages\CreateCustomer;
use App\Filament\App\Resources\Customers\Pages\EditCustomer;
use App\Filament\App\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    #[\Override]
    protected static ?string $model = Customer::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name'),
                TextInput::make('customer_last_name'),
                TextInput::make('customer_address'),
                TextInput::make('customer_email'),
                TextInput::make('customer_phone')
                    ->numeric(),
                TextInput::make('customer_city'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable()
                    ->label('First Name'),
                TextColumn::make('customer_last_name')
                    ->searchable()
                    ->sortable()
                    ->label('Last Name'),
                TextColumn::make('customer_email')
                    ->searchable()
                    ->sortable()
                    ->label('Email'),
                TextColumn::make('customer_phone')
                    ->label('Phone'),
                TextColumn::make('customer_city')
                    ->searchable()
                    ->label('City'),
                TextColumn::make('current_balance')
                    ->money('USD')
                    ->sortable()
                    ->label('Balance'),
                TextColumn::make('credit_limit')
                    ->money('USD')
                    ->sortable()
                    ->label('Credit Limit'),
                IconColumn::make('credit_hold')
                    ->boolean()
                    ->label('On Hold'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_city')
                    ->label('City')
                    ->options(fn () => Customer::distinct()->pluck('customer_city', 'customer_city')->toArray()),
                TernaryFilter::make('credit_hold')
                    ->label('Credit Status')
                    ->placeholder('All Customers')
                    ->trueLabel('On Credit Hold')
                    ->falseLabel('Not On Hold'),
                Filter::make('over_credit_limit')
                    ->label('Over Credit Limit')
                    ->query(fn ($query) => $query->whereRaw('current_balance >= credit_limit AND credit_limit > 0')),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
