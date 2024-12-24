<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BankStatementResource\Pages;
use App\Models\BankStatement;
use App\Services\ReconciliationService;
use Filament\Forms;
use Filament\Forms\Form;
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
            ->schema([
                Forms\Components\DatePicker::make('statement_date')
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Forms\Components\TextInput::make('total_credits')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_debits')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ending_balance')
                    ->required()
                    ->numeric(),
                FileUpload::make('statement_file')
                    ->label('Import Bank Statement')
                    ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateBankStatement)
                    ->helperText('Upload a CSV file with your bank statement data'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement_date')
                    ->date(),
                Tables\Columns\TextColumn::make('account.name'),
                Tables\Columns\TextColumn::make('total_credits')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total_debits')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('ending_balance')
                    ->money('USD'),
                Tables\Columns\IconColumn::make('reconciled')
                    ->boolean()
                    ->label('Reconciled'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reconcile')
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
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBankStatements::route('/'),
            'create' => Pages\CreateBankStatement::route('/create'),
            'edit' => Pages\EditBankStatement::route('/{record}/edit'),
        ];
    }
}
