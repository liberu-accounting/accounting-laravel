<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Payments;

use App\Filament\App\Resources\Payments\Pages\CreatePayment;
use App\Filament\App\Resources\Payments\Pages\EditPayment;
use App\Filament\App\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    #[\Override]
    protected static ?string $model = Payment::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
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

    #[\Override]
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
