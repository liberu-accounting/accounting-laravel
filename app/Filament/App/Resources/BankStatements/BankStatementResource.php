<?php

namespace App\Filament\App\Resources\BankStatements;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use App\Filament\App\Resources\BankStatements\Pages\CreateBankStatement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Exception;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\App\Resources\BankStatements\Pages\ListBankStatements;
use App\Filament\App\Resources\BankStatements\Pages\EditBankStatement;
use App\Filament\App\Resources\BankStatementResource\Pages;
use App\Models\BankStatement;
use App\Services\ReconciliationService;
use App\Services\BankStatementImportService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Banking';
    
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Statement Information')
                    ->schema([
                        DatePicker::make('statement_date')
                            ->label('Statement Date')
                            ->required()
                            ->helperText('The date of the bank statement'),
                        
                        Select::make('account_id')
                            ->relationship('account', 'name')
                            ->label('Bank Account')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the bank account for this statement'),
                    ])->columns(2),
                
                Section::make('Statement Balances')
                    ->schema([
                        TextInput::make('total_credits')
                            ->label('Total Credits')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Total credits on the statement'),
                        
                        TextInput::make('total_debits')
                            ->label('Total Debits')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Total debits on the statement'),
                        
                        TextInput::make('ending_balance')
                            ->label('Ending Balance')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Ending balance on the statement'),
                    ])->columns(3),
                
                Section::make('Import Transactions')
                    ->schema([
                        FileUpload::make('statement_file')
                            ->label('Import Bank Statement File')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/x-ofx'])
                            ->maxSize(5120)
                            ->visible(fn ($livewire) => $livewire instanceof CreateBankStatement)
                            ->helperText('Upload a CSV, Excel, or OFX file with your bank statement transactions'),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof CreateBankStatement)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('statement_date')
                    ->label('Statement Date')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('account.name')
                    ->label('Bank Account')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('total_credits')
                    ->label('Credits')
                    ->money('USD')
                    ->sortable(),
                
                TextColumn::make('total_debits')
                    ->label('Debits')
                    ->money('USD')
                    ->sortable(),
                
                TextColumn::make('ending_balance')
                    ->label('Ending Balance')
                    ->money('USD')
                    ->sortable(),
                
                IconColumn::make('reconciled')
                    ->label('Reconciled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                
                TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable()
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Date Range')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($query) => $query->whereDate('statement_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($query) => $query->whereDate('statement_date', '<=', $data['until'])
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('reconciled')
                    ->label('Reconciliation Status')
                    ->placeholder('All statements')
                    ->trueLabel('Reconciled')
                    ->falseLabel('Not reconciled'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                
                Action::make('import')
                    ->label('Import Transactions')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('statement_file')
                            ->label('Statement File')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/x-ofx'])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Upload CSV, Excel, or OFX file'),
                    ])
                    ->action(function (BankStatement $record, array $data) {
                        $importService = app(BankStatementImportService::class);
                        
                        if (isset($data['statement_file'])) {
                            $path = storage_path('app/public/' . $data['statement_file']);
                            $extension = pathinfo($path, PATHINFO_EXTENSION);
                            
                            try {
                                $transactions = match(strtolower($extension)) {
                                    'csv' => $importService->importFromCsv($path, $record),
                                    'ofx' => $importService->importFromOfx($path, $record),
                                    'xls', 'xlsx' => $importService->importFromCsv($path, $record), // Excel can be converted to CSV
                                    default => throw new Exception('Unsupported file format')
                                };
                                
                                Notification::make()
                                    ->title('Import Complete')
                                    ->body("Successfully imported {$transactions->count()} transactions")
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Import Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),
                
                Action::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BankStatement $record) => $record->transactions()->exists() && !$record->reconciled)
                    ->requiresConfirmation()
                    ->modalHeading('Reconcile Bank Statement')
                    ->modalDescription('This will attempt to match and reconcile all transactions for this statement.')
                    ->action(function (BankStatement $record) {
                        $reconciliationService = app(ReconciliationService::class);
                        $result = $reconciliationService->reconcile($record);
                        
                        $matched = $result['matched_transactions']->count();
                        $unmatched = $result['unmatched_transactions']->count();
                        $balanceDiscrepancy = abs($result['balance_discrepancy']);
                        
                        if ($unmatched === 0 && $balanceDiscrepancy < 0.01) {
                            $record->update(['reconciled' => true]);
                            
                            Notification::make()
                                ->title('Reconciliation Complete')
                                ->body("All {$matched} transactions matched successfully!")
                                ->success()
                                ->send();
                        } else {
                            $message = "Matched: {$matched}, Unmatched: {$unmatched}";
                            if ($balanceDiscrepancy >= 0.01) {
                                $message .= ", Balance discrepancy: $" . number_format($balanceDiscrepancy, 2);
                            }
                            
                            Notification::make()
                                ->title('Reconciliation Completed with Issues')
                                ->body($message)
                                ->warning()
                                ->send();
                        }
                    }),
                
                Action::make('view_discrepancies')
                    ->label('View Discrepancies')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (BankStatement $record) => $record->transactions()->exists())
                    ->modalHeading('Reconciliation Discrepancies')
                    ->modalContent(function (BankStatement $record) {
                        $reconciliationService = app(ReconciliationService::class);
                        $result = $reconciliationService->reconcile($record);
                        
                        return view('filament.modals.reconciliation-discrepancies', [
                            'result' => $result,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('statement_date', 'desc');
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
            'index' => ListBankStatements::route('/'),
            'create' => CreateBankStatement::route('/create'),
            'edit' => EditBankStatement::route('/{record}/edit'),
        ];
    }
}
