<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    #[\Override]
    protected string $view = 'filament.pages.profile.update-profile-information';

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    #[\Override]
    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    #[\Override]
    protected static ?int $navigationSort = 0;

    #[\Override]
    protected static ?string $title = 'Profile';

    public ?string $name = null;

    public ?string $email = null;

    public function mount(): void
    {
        $this->form->fill([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Name')
                ->required(),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required(),
        ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        Auth::user()->forceFill(array_filter([
            'name' => $state['name'] ?? null,
            'email' => $state['email'] ?? null,
        ]))->save();

        session()->flash('status', 'Your profile has been updated.');
    }

    #[\Override]
    public function getHeading(): string|Htmlable
    {
        return static::$title ?? '';
    }

    #[\Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
