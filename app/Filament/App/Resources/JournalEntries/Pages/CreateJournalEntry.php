<?php

namespace App\Filament\App\Resources\JournalEntries\Pages;

use App\Filament\App\Resources\JournalEntries\JournalEntryResource;
use App\Rules\DoubleEntryValidator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        return $data;
    }

    protected function beforeValidate(): void
    {
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
