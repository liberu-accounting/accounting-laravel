<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\CustomerResource\Pages;
use App\Filament\App\Resources\CustomerResource\RelationManagers;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('customer_name'),
                TextInput::make('customer_last_name'),
                TextInput::make('customer_address'),
                TextInput::make('customer_email'),
                TextInput::make('customer_phone')
                    ->numeric(),
                TextInput::make('customer_city'),
            ]);
    }

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
                Tables\Columns\IconColumn::make('credit_hold')
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
                Tables\Filters\SelectFilter::make('customer_city')
                    ->label('City')
                    ->options(fn () => \App\Models\Customer::distinct()->pluck('customer_city', 'customer_city')->toArray()),
                Tables\Filters\TernaryFilter::make('credit_hold')
                    ->label('Credit Status')
                    ->placeholder('All Customers')
                    ->trueLabel('On Credit Hold')
                    ->falseLabel('Not On Hold'),
                Tables\Filters\Filter::make('over_credit_limit')
                    ->label('Over Credit Limit')
                    ->query(fn ($query) => $query->whereRaw('current_balance >= credit_limit AND credit_limit > 0')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}