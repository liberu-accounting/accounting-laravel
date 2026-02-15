<?php

namespace App\Filament\App\Resources\BankConnections;

use App\Filament\App\Resources\BankConnections\Pages\CreateBankConnection;
use App\Filament\App\Resources\BankConnections\Pages\EditBankConnection;
use App\Filament\App\Resources\BankConnections\Pages\ListBankConnections;
use App\Filament\App\Resources\BankConnections\Pages\ViewBankConnection;
use App\Models\BankConnection;
use App\Services\PlaidService;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Exception;

class BankConnectionResource extends Resource
{
    protected static ?string $model = BankConnection::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    
    protected static ?string $navigationGroup = 'Banking';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Connection Details')
                    ->schema([
                        TextInput::make('institution_name')
                            ->label('Bank/Institution Name')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('bank_id')
                            ->label('Bank ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Internal identifier for this bank connection'),
                        
                        Select::make('status')
                            ->label('Connection Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'error' => 'Error',
                                'pending' => 'Pending',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(2),
                
                Section::make('Plaid Integration')
                    ->schema([
                        TextInput::make('plaid_item_id')
                            ->label('Plaid Item ID')
                            ->maxLength(255)
                            ->disabled()
                            ->helperText('Automatically populated when connected via Plaid'),
                        
                        TextInput::make('plaid_institution_id')
                            ->label('Plaid Institution ID')
                            ->maxLength(255)
                            ->disabled()
                            ->helperText('Plaid institution identifier'),
                        
                        DateTimePicker::make('last_synced_at')
                            ->label('Last Synced')
                            ->disabled()
                            ->helperText('Last time transactions were synced from Plaid'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record || !$record->plaid_item_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('institution_name')
                    ->label('Institution')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('bank_id')
                    ->label('Bank ID')
                    ->searchable()
                    ->toggleable(),
                
                IconColumn::make('status')
                    ->label('Status')
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        'error' => 'heroicon-o-exclamation-circle',
                        'pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'error' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                
                TextColumn::make('plaid_item_id')
                    ->label('Plaid Connected')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),
                
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                
                TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'error' => 'Error',
                        'pending' => 'Pending',
                    ]),
                
                Tables\Filters\TernaryFilter::make('plaid_connected')
                    ->label('Plaid Connected')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('plaid_item_id'),
                        false: fn ($query) => $query->whereNull('plaid_item_id'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                
                Action::make('sync_transactions')
                    ->label('Sync Transactions')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn (BankConnection $record) => $record->plaid_item_id !== null)
                    ->requiresConfirmation()
                    ->action(function (BankConnection $record) {
                        try {
                            $plaidService = app(PlaidService::class);
                            $result = $plaidService->syncTransactions($record);
                            
                            $addedCount = count($result['added'] ?? []);
                            $modifiedCount = count($result['modified'] ?? []);
                            $removedCount = count($result['removed'] ?? []);
                            
                            Notification::make()
                                ->title('Transactions Synced Successfully')
                                ->body("Added: {$addedCount}, Modified: {$modifiedCount}, Removed: {$removedCount}")
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Action::make('disconnect')
                    ->label('Disconnect')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (BankConnection $record) => $record->plaid_item_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Disconnect Bank Connection')
                    ->modalDescription('Are you sure you want to disconnect this bank? This will remove the Plaid connection but keep historical transaction data.')
                    ->action(function (BankConnection $record) {
                        try {
                            $plaidService = app(PlaidService::class);
                            $plaidService->removeItem($record->plaid_access_token);
                            
                            $record->update([
                                'status' => 'inactive',
                                'plaid_access_token' => null,
                                'plaid_item_id' => null,
                                'plaid_institution_id' => null,
                                'plaid_cursor' => null,
                            ]);
                            
                            Notification::make()
                                ->title('Bank Disconnected')
                                ->body('The bank connection has been removed.')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Disconnect Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                DeleteAction::make(),
            ])
            ->toolbarActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Connection'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // We can add relation managers here later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankConnections::route('/'),
            'create' => CreateBankConnection::route('/create'),
            'view' => ViewBankConnection::route('/{record}'),
            'edit' => EditBankConnection::route('/{record}/edit'),
        ];
    }
}
