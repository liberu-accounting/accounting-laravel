<?php

namespace App\Filament\App\Resources\HmrcVatReturns;

use App\Filament\App\Resources\HmrcVatReturns\Pages\ListHmrcVatReturns;
use App\Filament\App\Resources\HmrcVatReturns\Pages\CreateHmrcVatReturn;
use App\Filament\App\Resources\HmrcVatReturns\Pages\EditHmrcVatReturn;
use App\Models\HmrcVatReturn;
use App\Services\HmrcMtdVatService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;

class HmrcVatReturnResource extends Resource
{
    protected static ?string $model = HmrcVatReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'VAT Returns';

    protected static ?string $navigationGroup = 'HMRC Submissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Period Information')
                    ->schema([
                        Forms\Components\TextInput::make('period_key')
                            ->label('Period Key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('HMRC VAT period key (e.g., 23A1)'),
                        Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('period_from')
                                    ->required()
                                    ->label('Period From'),
                                Forms\Components\DatePicker::make('period_to')
                                    ->required()
                                    ->label('Period To'),
                                Forms\Components\DatePicker::make('due_date')
                                    ->required()
                                    ->label('Due Date'),
                            ]),
                    ]),
                
                Section::make('VAT Amounts')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('vat_due_sales')
                                    ->label('Box 1: VAT due on sales')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('vat_due_acquisitions')
                                    ->label('Box 2: VAT due on EC acquisitions')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('total_vat_due')
                                    ->label('Box 3: Total VAT due')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                                Forms\Components\TextInput::make('vat_reclaimed')
                                    ->label('Box 4: VAT reclaimed')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('net_vat_due')
                                    ->label('Box 5: Net VAT due')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                            ]),
                    ]),
                
                Section::make('Turnover Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_value_sales')
                                    ->label('Box 6: Total sales (ex VAT)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('total_value_purchases')
                                    ->label('Box 7: Total purchases (ex VAT)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('total_value_goods_supplied')
                                    ->label('Box 8: EC goods supplied')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                                Forms\Components\TextInput::make('total_acquisitions')
                                    ->label('Box 9: EC acquisitions')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('£')
                                    ->default(0),
                            ]),
                    ]),
                
                Forms\Components\Toggle::make('finalised')
                    ->label('Finalised')
                    ->helperText('Mark as finalised to enable submission'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_key')
                    ->searchable()
                    ->label('Period'),
                Tables\Columns\TextColumn::make('period_from')
                    ->date()
                    ->label('From'),
                Tables\Columns\TextColumn::make('period_to')
                    ->date()
                    ->label('To'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->label('Due Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_vat_due')
                    ->money('GBP')
                    ->label('Net VAT Due'),
                Tables\Columns\BadgeColumn::make('status')
                    ->getStateUsing(fn ($record) => $record->status)
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'ready',
                        'primary' => 'submitted',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('finalised')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'ready' => 'Ready',
                        'submitted' => 'Submitted',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\TernaryFilter::make('finalised'),
            ])
            ->actions([
                Tables\Actions\Action::make('calculate')
                    ->label('Calculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function (HmrcVatReturn $record) {
                        $record->calculateFromTransactions();
                        Notification::make()
                            ->title('VAT return calculated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (HmrcVatReturn $record) => $record->isEditable()),
                Tables\Actions\Action::make('submit')
                    ->label('Submit to HMRC')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (HmrcVatReturn $record) {
                        try {
                            $service = app(HmrcMtdVatService::class);
                            $service->submitVatReturn($record);
                            Notification::make()
                                ->title('VAT return submitted successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Submission failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (HmrcVatReturn $record) => $record->finalised && $record->isEditable()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (HmrcVatReturn $record) => $record->isEditable()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHmrcVatReturns::route('/'),
            'create' => CreateHmrcVatReturn::route('/create'),
            'edit' => EditHmrcVatReturn::route('/{record}/edit'),
        ];
    }
}
