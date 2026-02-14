<?php

namespace App\Filament\App\Resources\JournalEntries;

use App\Filament\App\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\App\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\App\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Models\JournalEntry;
use App\Models\Account;
use App\Rules\DoubleEntryValidator;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Journal Entries';

    protected static ?string $navigationGroup = 'Accounting';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Entry Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('entry_number')
                                    ->label('Entry Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn () => 'Auto-generated'),
                                DatePicker::make('entry_date')
                                    ->label('Entry Date')
                                    ->required()
                                    ->default(now()),
                                Select::make('entry_type')
                                    ->label('Entry Type')
                                    ->options([
                                        'general' => 'General Journal',
                                        'adjusting' => 'Adjusting Entry',
                                        'closing' => 'Closing Entry',
                                        'reversing' => 'Reversing Entry',
                                    ])
                                    ->default('general')
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('reference_number')
                                    ->label('Reference Number')
                                    ->maxLength(255),
                                Textarea::make('memo')
                                    ->label('Memo')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Journal Entry Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('account_id')
                                            ->label('Account')
                                            ->options(function () {
                                                return Account::where('is_active', true)
                                                    ->whereDoesntHave('children')
                                                    ->orderBy('account_number')
                                                    ->get()
                                                    ->mapWithKeys(function ($account) {
                                                        return [$account->id => $account->account_number . ' - ' . $account->account_name];
                                                    });
                                            })
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(4),
                                        TextInput::make('debit_amount')
                                            ->label('Debit')
                                            ->numeric()
                                            ->default(0)
                                            ->step('0.01')
                                            ->prefix('$')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) => 
                                                $state > 0 ? $set('credit_amount', 0) : null
                                            )
                                            ->columnSpan(2),
                                        TextInput::make('credit_amount')
                                            ->label('Credit')
                                            ->numeric()
                                            ->default(0)
                                            ->step('0.01')
                                            ->prefix('$')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) => 
                                                $state > 0 ? $set('debit_amount', 0) : null
                                            )
                                            ->columnSpan(2),
                                        TextInput::make('description')
                                            ->label('Description')
                                            ->maxLength(255)
                                            ->columnSpan(4),
                                    ]),
                            ])
                            ->columns(1)
                            ->defaultItems(2)
                            ->minItems(2)
                            ->addActionLabel('Add Line')
                            ->reorderable(false)
                            ->columnSpanFull(),
                        
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('total_debits')
                                    ->label('Total Debits')
                                    ->content(function ($get) {
                                        $lines = $get('lines') ?? [];
                                        $total = collect($lines)->sum('debit_amount');
                                        return new HtmlString('<span class="text-lg font-bold">$' . number_format($total, 2) . '</span>');
                                    }),
                                Placeholder::make('total_credits')
                                    ->label('Total Credits')
                                    ->content(function ($get) {
                                        $lines = $get('lines') ?? [];
                                        $total = collect($lines)->sum('credit_amount');
                                        return new HtmlString('<span class="text-lg font-bold">$' . number_format($total, 2) . '</span>');
                                    }),
                                Placeholder::make('balance')
                                    ->label('Difference')
                                    ->content(function ($get) {
                                        $lines = $get('lines') ?? [];
                                        $debits = collect($lines)->sum('debit_amount');
                                        $credits = collect($lines)->sum('credit_amount');
                                        $diff = $debits - $credits;
                                        $color = $diff == 0 ? 'text-green-600' : 'text-red-600';
                                        return new HtmlString('<span class="text-lg font-bold ' . $color . '">$' . number_format($diff, 2) . '</span>');
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_number')
                    ->label('Entry #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entry_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'adjusting' => 'warning',
                        'closing' => 'danger',
                        'reversing' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('memo')
                    ->label('Memo')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('total_debits')
                    ->label('Amount')
                    ->money('usd')
                    ->getStateUsing(fn ($record) => $record->lines()->sum('debit_amount')),
                IconColumn::make('is_posted')
                    ->boolean()
                    ->label('Posted')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('posted_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->options([
                        'general' => 'General Journal',
                        'adjusting' => 'Adjusting Entry',
                        'closing' => 'Closing Entry',
                        'reversing' => 'Reversing Entry',
                    ]),
                SelectFilter::make('is_posted')
                    ->label('Status')
                    ->options([
                        1 => 'Posted',
                        0 => 'Unposted',
                    ]),
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('post')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_posted)
                    ->action(function ($record) {
                        try {
                            $record->post();
                            \Filament\Notifications\Notification::make()
                                ->title('Journal entry posted successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error posting journal entry')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reverse')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->is_posted)
                    ->action(function ($record) {
                        try {
                            $record->reverse();
                            \Filament\Notifications\Notification::make()
                                ->title('Journal entry reversed successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error reversing journal entry')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_posted),
            ])
            ->defaultSort('entry_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
