<?php

namespace App\Filament\App\Resources\BankConnections\Pages;

use App\Filament\App\Resources\BankConnections\BankConnectionResource;
use App\Services\PlaidService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Exception;

class ListBankConnections extends ListRecords
{
    protected static string $resource = BankConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Manual Connection'),
            
            Action::make('connect_plaid')
                ->label('Connect via Plaid')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->action(function () {
                    try {
                        $plaidService = app(PlaidService::class);
                        $userId = auth()->id();
                        
                        $linkToken = $plaidService->createLinkToken($userId);
                        
                        // Store link token in session for the frontend to use
                        session(['plaid_link_token' => $linkToken['link_token']]);
                        
                        Notification::make()
                            ->title('Ready to Connect')
                            ->body('Plaid Link is ready. Please complete the connection flow.')
                            ->info()
                            ->send();
                        
                        // Return redirect to a page that will initialize Plaid Link
                        // This would typically be a custom Livewire component
                        return redirect()->to('/app/plaid-connect');
                        
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Connection Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Connect Bank via Plaid')
                ->modalDescription('You will be redirected to securely connect your bank account through Plaid.')
                ->requiresConfirmation(),
        ];
    }
}
