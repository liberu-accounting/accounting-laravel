<?php

namespace App\Filament\App\Resources\CreditMemos;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\CreditMemos\Pages\ListCreditMemos;
use App\Filament\App\Resources\CreditMemos\Pages\CreateCreditMemo;
use App\Filament\App\Resources\CreditMemos\Pages\EditCreditMemo;
use Filament\Forms;
use Filament\Tables;
use App\Models\CreditMemo;
use App\Models\TaxRate;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CreditMemoResource extends Resource
{
    protected static ?string $model = CreditMemo::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?int $navigationSort = 4;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Sales';
    
    protected static ?string $recordTitleAttribute = 'credit_memo_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload(),
                    
                TextInput::make('credit_memo_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
                    
                DatePicker::make('credit_memo_date')
                    ->required()
                    ->default(now()),
                    
                Select::make('reason')
                    ->options([
                        'product_return' => 'Product Return',
                        'billing_error' => 'Billing Error',
                        'discount' => 'Discount',
                        'other' => 'Other',
                    ]),
                    
                Select::make('tax_rate_id')
                    ->relationship('taxRate', 'name')
                    ->reactive(),
                    
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->reactive(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->collapsible(),
                    
                TextInput::make('subtotal_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                TextInput::make('tax_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                TextInput::make('total_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                TextInput::make('amount_applied')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'applied' => 'Applied',
                        'void' => 'Void',
                    ])
                    ->default('draft')
                    ->required(),
                    
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_memo_number')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('credit_memo_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('amount_applied')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('amount_remaining')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->amount_remaining),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'open',
                        'success' => 'applied',
                        'warning' => 'void',
                    ]),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'applied' => 'Applied',
                        'void' => 'Void',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('credit_memo_date', 'desc');
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
            'index' => ListCreditMemos::route('/'),
            'create' => CreateCreditMemo::route('/create'),
            'edit' => EditCreditMemo::route('/{record}/edit'),
        ];
    }
}
