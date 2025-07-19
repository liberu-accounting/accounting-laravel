<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Filament\App\Resources\BankStatementResource\Pages\CreateBankStatement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\BankStatementResource\Pages\ListBankStatements;
use App\Filament\App\Resources\BankStatementResource\Pages\EditBankStatement;
use App\Filament\App\Resources\BankStatementResource\Pages;
use App\Models\BankStatement;
use App\Services\ReconciliationService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                DatePicker::make('statement_date')
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                TextInput::make('total_credits')
                    ->required()
                    ->numeric(),
                TextInput::make('total_debits')
                    ->required()
                    ->numeric(),
                TextInput::make('ending_balance')
                    ->required()
                    ->numeric(),
                FileUpload::make('statement_file')
                    ->label('Import Bank Statement')
                    ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                    ->visible(fn ($livewire) => $livewire instanceof CreateBankStatement)
                    ->helperText('Upload a CSV file with your bank statement data'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('statement_date')
                    ->date(),
                TextColumn::make('account.name'),
                TextColumn::make('total_credits')
                    ->money('USD'),
                TextColumn::make('total_debits')
                    ->money('USD'),
                TextColumn::make('ending_balance')
                    ->money('USD'),
                IconColumn::make('reconciled')
                    ->boolean()
                    ->label('Reconciled'),
            ])
            ->filters([
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
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
                    })
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('import')
                    ->action(function (BankStatement $record, array $data) {
                        $importService = new BankStatementImportService();
                        
                        if ($data['statement_file']) {
                            $path = storage_path('app/' . $data['statement_file']);
                            $extension = pathinfo($path, PATHINFO_EXTENSION);
                            
                            $transactions = match($extension) {
                                'csv' => $importService->importFromCsv($path, $record),
                                'ofx' => $importService->importFromOfx($path, $record),
                                default => throw new Exception('Unsupported file format')
                            };
                            
                            Notification::make()
                                ->title('Import Complete')
                                ->body("Imported {$transactions->count()} transactions")
                                ->success()
                                ->send();
                        }
                    })
                    ->schema([
                        FileUpload::make('statement_file')
                            ->label('Import Statement')
                            ->acceptedFileTypes(['.csv', '.ofx'])
                            ->required()
                    ])
                    ->icon('heroicon-o-arrow-up-tray'),
                Action::make('review_matches')
                    ->action(function (BankStatement $record) {
                        return view('bank-statements.review-matches', [
                            'bankStatement' => $record,
                            'reconciliation' => (new ReconciliationService())->reconcile($record)
                        ]);
                    })
                    ->icon('heroicon-o-eye')
                    ->visible(fn (BankStatement $record) => $record->transactions()->exists()),
                Action::make('reconcile')
                    ->action(function (BankStatement $record) {
                        $reconciliationService = new ReconciliationService();
                        $result = $reconciliationService->reconcile($record);
                        
                        Notification::make()
                            ->title('Reconciliation Complete')
                            ->body(view('bank-statements.reconciliation-result', [
                                'matched' => $result['matched_transactions']->count(),
                                'unmatched' => $result['unmatched_transactions']->count(),
                                'discrepancies' => $result['discrepancies'],
                                'balance_discrepancy' => $result['balance_discrepancy']
                            ]))
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListBankStatements::route('/'),
            'create' => CreateBankStatement::route('/create'),
            'edit' => EditBankStatement::route('/{record}/edit'),
        ];
    }
}
