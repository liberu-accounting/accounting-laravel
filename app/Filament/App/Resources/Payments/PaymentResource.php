<?php

namespace App\Filament\App\Resources\Payments;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\Payments\Pages\ListPayments;
use App\Filament\App\Resources\Payments\Pages\CreatePayment;
use App\Filament\App\Resources\Payments\Pages\EditPayment;
use Filament\Forms;
use Filament\Tables;
use App\Models\Payment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\PaymentResource\Pages;
use App\Filament\App\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_id')
                    ->label('Invoice'),
                DatePicker::make('payment_date')->label('Payment Date'),
                TextInput::make('payment_amount')
                    ->numeric()
                    ->label('Payment Amount'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_amount')
                    ->label('Payment Amount')
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
