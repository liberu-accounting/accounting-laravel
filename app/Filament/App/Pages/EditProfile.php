<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

class EditProfile extends Page
{
    #[\Override]
    protected string $view = 'filament.pages.edit-profile';

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public User $user;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Confirmation code entered while enrolling in 2FA. */
    public string $twoFactorCode = '';

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->form->fill([
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required()
                ->maxLength(255),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $this->user->forceFill([
            'name' => $state['name'],
            'email' => $state['email'],
        ])->save();

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    /**
     * Begin 2FA enrolment: generates the secret + recovery codes. The user is
     * not yet enrolled until they confirm a code (Fortify `confirm => true`).
     */
    public function enableTwoFactor(EnableTwoFactorAuthentication $enable): void
    {
        $enable($this->user);
        $this->user->refresh();

        Notification::make()
            ->title('Scan the QR code with your authenticator app, then confirm with a generated code.')
            ->success()
            ->send();
    }

    /** Finalise enrolment by verifying a code from the authenticator app. */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirm): void
    {
        try {
            $confirm($this->user, $this->twoFactorCode);
        } catch (ValidationException) {
            Notification::make()
                ->title('The provided two-factor code was invalid.')
                ->danger()
                ->send();

            return;
        }

        $this->user->refresh();
        $this->twoFactorCode = '';

        Notification::make()
            ->title('Two-factor authentication enabled.')
            ->success()
            ->send();
    }

    /** Turn 2FA off (also cancels a pending, unconfirmed enrolment). */
    public function disableTwoFactor(DisableTwoFactorAuthentication $disable): void
    {
        $disable($this->user);
        $this->user->refresh();
        $this->twoFactorCode = '';

        Notification::make()
            ->title('Two-factor authentication disabled.')
            ->success()
            ->send();
    }

    #[\Override]
    public function getBreadcrumbs(): array
    {
        return [url()->current() => 'Edit Profile'];
    }
}
