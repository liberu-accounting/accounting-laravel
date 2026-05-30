<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    #[\Override]
    protected string $view = 'filament.pages.profile.update-profile-information';

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';

    #[\Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Account';

    #[\Override]
    protected static ?int $navigationSort = 0;

    #[\Override]
    protected static ?string $title = 'Profile';

    public $name;

    public $email;

    public function mount(): void
    {
        $this->form->fill([
            'name'  => Auth::user()->name,
            'email' => Auth::user()->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
            TextInput::make('email')
                ->label('Email Address')
                ->required(),
        ];
    }

    public function submit(): void
    {
        $this->form->getState();

        $state = array_filter([
            'name'  => $this->name,
            'email' => $this->email,
        ]);

        $user = Auth::user();

        $user->forceFill($state)->save();

        session()->flash('status', 'Your profile has been updated.');
    }

    #[\Override]
    public function getHeading(): string
    {
        return static::$title;
    }

    #[\Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true; //config('filament-jetstream.show_update_profile_information_page');
    }
}
