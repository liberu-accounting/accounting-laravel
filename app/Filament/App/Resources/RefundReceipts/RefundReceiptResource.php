<?php

namespace App\Filament\App\Resources\RefundReceipts;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\RefundReceipts\Pages\ListRefundReceipts;
use App\Filament\App\Resources\RefundReceipts\Pages\CreateRefundReceipt;
use App\Filament\App\Resources\RefundReceipts\Pages\EditRefundReceipt;
use Filament\Forms;
use Filament\Tables;
use App\Models\RefundReceipt;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class RefundReceiptResource extends Resource
{
    protected static ?string $model = RefundReceipt::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-uturn-left';
    
    protected static ?int $navigationSort = 7;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Sales';
    
    protected static ?string $recordTitleAttribute = 'refund_receipt_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Refund Information')
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'customer_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        TextInput::make('refund_receipt_number')
                            ->label('Refund #')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                            
                        DatePicker::make('refund_date')
                            ->required()
                            ->default(now()),
                            
                        Select::make('sales_receipt_id')
                            ->relationship('salesReceipt', 'sales_receipt_number')
                            ->label('Original Sales Receipt')
                            ->searchable()
                            ->preload(),
                            
                        Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->label('Original Invoice')
                            ->searchable()
                            ->preload(),
                            
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'debit_card' => 'Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('cash'),
                            
                        TextInput::make('reference_number')
                            ->label('Reference #'),
                            
                        Select::make('refund_from_account_id')
                            ->relationship('refundAccount', 'name')
                            ->label('Refund From')
                            ->searchable()
                            ->preload(),
                            
                        Select::make('reason')
                            ->options([
                                'product_return' => 'Product Return',
                                'overpayment' => 'Overpayment',
                                'service_not_rendered' => 'Service Not Rendered',
                                'customer_dissatisfaction' => 'Customer Dissatisfaction',
                                'other' => 'Other',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Section::make('Line Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->searchable()
                                    ->preload(),
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
                            ->columns(5)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),
                    
                Section::make('Totals')
                    ->schema([
                        TextInput::make('subtotal_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                            
                        TextInput::make('tax_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                            
                        TextInput::make('total_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                    ])
                    ->columns(3),
                    
                Section::make('Additional Information')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Completed',
                                'void' => 'Void',
                            ])
                            ->default('draft')
                            ->required(),
                            
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refund_receipt_number')
                    ->label('Refund #')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('refund_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'completed',
                        'danger' => 'void',
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
                        'completed' => 'Completed',
                        'void' => 'Void',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'check' => 'Check',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->process()),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'void')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->void()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('refund_date', 'desc');
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
            'index' => ListRefundReceipts::route('/'),
            'create' => CreateRefundReceipt::route('/create'),
            'edit' => EditRefundReceipt::route('/{record}/edit'),
        ];
    }
}
