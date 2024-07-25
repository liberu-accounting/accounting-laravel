<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\GeneralLedgerReportResource\Pages;
use App\Models\GeneralLedgerReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GeneralLedgerReportResource extends Resource
{
    protected static ?string $model = GeneralLedgerReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('report_date')
                    ->required(),
                Forms\Components\Select::make('report_type')
                    ->options([
                        'balance_sheet' => 'Balance Sheet',
                        'income_statement' => 'Income Statement',
                        'cash_flow' => 'Cash Flow Statement',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_date')
                    ->date(),
                Tables\Columns\TextColumn::make('report_type'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGeneralLedgerReports::route('/'),
            'create' => Pages\CreateGeneralLedgerReport::route('/create'),
            'view' => Pages\ViewGeneralLedgerReport::route('/{record}'),
            'edit' => Pages\EditGeneralLedgerReport::route('/{record}/edit'),
        ];
    }    
}
