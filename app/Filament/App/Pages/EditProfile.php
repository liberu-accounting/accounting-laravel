<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EditProfile extends Page
{
    #[\Override]
    protected string $view = 'filament.pages.edit-profile';

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public User $user;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->form->fill([
            'name'  => $this->user->name,
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
        ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $this->user->forceFill([
            'name'  => $state['name'],
            'email' => $state['email'],
        ])->save();

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    #[\Override]
    public function getBreadcrumbs(): array
    {
        return [url()->current() => 'Edit Profile'];
    }
}
