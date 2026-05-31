<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DelayedCharges;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\DelayedCharges\Pages\ListDelayedCharges;
use App\Filament\App\Resources\DelayedCharges\Pages\CreateDelayedCharge;
use App\Filament\App\Resources\DelayedCharges\Pages\EditDelayedCharge;
use Filament\Forms;
use Filament\Tables;
use App\Models\DelayedCharge;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class DelayedChargeResource extends Resource
{
    #[\Override]
    protected static ?string $model = DelayedCharge::class;
    
    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    
    #[\Override]
    protected static ?int $navigationSort = 6;
    
    #[\Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Sales';
    
    #[\Override]
    protected static ?string $recordTitleAttribute = 'description';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Charge Information')
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'customer_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        DatePicker::make('charge_date')
                            ->required()
                            ->default(now()),
                            
                        Select::make('account_id')
                            ->relationship('account', 'name')
                            ->label('Income Account')
                            ->searchable()
                            ->preload(),
                            
                        TextInput::make('description')
                            ->required()
                            ->columnSpanFull(),
                            
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get): void {
                                $unitPrice = $get('unit_price') ?? 0;
                                $set('amount', $state * $unitPrice);
                            }),
                            
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get): void {
                                $quantity = $get('quantity') ?? 1;
                                $set('amount', $quantity * $state);
                            }),
                            
                        TextInput::make('amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                    ])
                    ->columns(2),
                    
                Section::make('Status & Notes')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'invoiced' => 'Invoiced',
                                'void' => 'Void',
                            ])
                            ->default('pending')
                            ->required(),
                            
                        Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->label('Added to Invoice')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->visible(fn ($record): bool => $record !== null && $record->invoice_id),
                            
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('charge_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                    
                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'invoiced',
                        'danger' => 'void',
                    ]),
                    
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'invoiced' => 'Invoiced',
                        'void' => 'Void',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('add_to_invoice')
                    ->label('Add to Invoice')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->form([
                        Select::make('invoice_id')
                            ->label('Invoice')
                            ->relationship('invoice', 'invoice_number')
                            ->required()
                            ->searchable(),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data): void {
                        $record->addToInvoice($data['invoice_id']);
                    }),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->void()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('charge_date', 'desc');
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
            'index' => ListDelayedCharges::route('/'),
            'create' => CreateDelayedCharge::route('/create'),
            'edit' => EditDelayedCharge::route('/{record}/edit'),
        ];
    }
}
