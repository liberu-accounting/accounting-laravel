<?php

namespace App\Filament\App\Resources\JournalEntries\Pages;

use App\Filament\App\Resources\JournalEntries\JournalEntryResource;
use App\Rules\DoubleEntryValidator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_posted),
        ];
    }

    protected function beforeValidate(): void
    {
        // Don't allow editing posted entries
        if ($this->record->is_posted) {
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Cannot edit posted journal entry')
                ->body('Please reverse the entry first before editing.')
                ->danger()
                ->send();
            return;
        }

        $lines = $this->data['lines'] ?? [];
        
        $validator = new DoubleEntryValidator($lines);
        
        if (!$validator->passes('lines', $lines)) {
            $this->addError('lines', $validator->message());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
