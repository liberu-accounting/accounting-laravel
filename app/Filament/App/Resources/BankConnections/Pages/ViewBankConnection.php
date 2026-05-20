<?php

namespace App\Filament\App\Resources\BankConnections\Pages;

use App\Filament\App\Resources\BankConnections\BankConnectionResource;
use App\Services\PlaidService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Exception;

class ViewBankConnection extends ViewRecord
{
    protected static string $resource = BankConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Action::make('sync_transactions')
                ->label('Sync Transactions')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => $this->record->plaid_item_id !== null)
                ->action(function () {
                    try {
                        $plaidService = app(PlaidService::class);
                        $result = $plaidService->syncTransactions($this->record);
                        
                        $addedCount = count($result['added'] ?? []);
                        $modifiedCount = count($result['modified'] ?? []);
                        $removedCount = count($result['removed'] ?? []);
                        
                        Notification::make()
                            ->title('Transactions Synced Successfully')
                            ->body("Added: {$addedCount}, Modified: {$modifiedCount}, Removed: {$removedCount}")
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
