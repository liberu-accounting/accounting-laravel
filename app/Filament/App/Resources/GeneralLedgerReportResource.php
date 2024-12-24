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

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('report_date')
                    ->required(),
                Forms\Components\Select::make('report_type')
                    ->options(GeneralLedgerReport::REPORT_TYPES)
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('template_name')
                    ->required(fn ($get) => $get('is_template'))
                    ->visible(fn ($get) => $get('is_template')),
                Forms\Components\Toggle::make('is_template')
                    ->label('Save as Template'),
                Forms\Components\Select::make('chart_type')
                    ->options(GeneralLedgerReport::CHART_TYPES)
                    ->default('none')
                    ->visible(fn ($get) => $get('report_type') !== 'custom'),
                Forms\Components\KeyValue::make('filters')
                    ->label('Report Filters'),
                Forms\Components\KeyValue::make('custom_fields')
                    ->label('Custom Fields')
                    ->visible(fn ($get) => $get('report_type') === 'custom'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('template_name')
                    ->searchable()
                    ->visible(fn ($record) => $record->is_template),
                Tables\Columns\IconColumn::make('is_template')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('report_type')
                    ->options(GeneralLedgerReport::REPORT_TYPES),
                Tables\Filters\TernaryFilter::make('is_template')
                    ->label('Template Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->icon('heroicon-o-document-download')
                    ->action(fn ($record) => $record->generatePdf()),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record) {
                        $new = $record->replicate();
                        $new->template_name = $new->template_name . ' (Copy)';
                        $new->save();
                    }),
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
