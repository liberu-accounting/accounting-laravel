<?php

namespace App\Filament\App\Resources\GeneralLedgerReports;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\GeneralLedgerReports\Pages\ListGeneralLedgerReports;
use App\Filament\App\Resources\GeneralLedgerReports\Pages\CreateGeneralLedgerReport;
use App\Filament\App\Resources\GeneralLedgerReports\Pages\ViewGeneralLedgerReport;
use App\Filament\App\Resources\GeneralLedgerReports\Pages\EditGeneralLedgerReport;
use App\Filament\App\Resources\GeneralLedgerReportResource\Pages;
use App\Models\GeneralLedgerReport;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GeneralLedgerReportResource extends Resource
{
    protected static ?string $model = GeneralLedgerReport::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('report_date')
                    ->required(),
                Select::make('report_type')
                    ->options(GeneralLedgerReport::REPORT_TYPES)
                    ->required()
                    ->reactive(),
                TextInput::make('template_name')
                    ->required(fn ($get) => $get('is_template'))
                    ->visible(fn ($get) => $get('is_template')),
                Toggle::make('is_template')
                    ->label('Save as Template'),
                Select::make('chart_type')
                    ->options(GeneralLedgerReport::CHART_TYPES)
                    ->default('none')
                    ->visible(fn ($get) => $get('report_type') !== 'custom'),
                KeyValue::make('filters')
                    ->label('Report Filters'),
                KeyValue::make('custom_fields')
                    ->label('Custom Fields')
                    ->visible(fn ($get) => $get('report_type') === 'custom'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('report_type')
                    ->searchable(),
                TextColumn::make('template_name')
                    ->searchable()
                    ->visible(fn ($record) => $record->is_template),
                IconColumn::make('is_template')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('report_type')
                    ->options(GeneralLedgerReport::REPORT_TYPES),
                TernaryFilter::make('is_template')
                    ->label('Template Status'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download_pdf')
                    ->icon('heroicon-o-document-download')
                    ->action(fn ($record) => $record->generatePdf()),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record) {
                        $new = $record->replicate();
                        $new->template_name = $new->template_name . ' (Copy)';
                        $new->save();
                    }),
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
            'index' => ListGeneralLedgerReports::route('/'),
            'create' => CreateGeneralLedgerReport::route('/create'),
            'view' => ViewGeneralLedgerReport::route('/{record}'),
            'edit' => EditGeneralLedgerReport::route('/{record}/edit'),
        ];
    }    
}
