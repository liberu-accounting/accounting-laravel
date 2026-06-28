<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTeam extends EditTenantProfile
{
    #[\Override]
    protected string $view = 'filament.pages.edit-team';

    public static function getLabel(): string
    {
        return 'Edit Team';
    }

    #[\Override]
    public function mount(): void
    {
        abort_unless($this->user()->canCreateTeams(), 403);
    }

    #[\Override]
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Team Name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function submit(): void
    {
        $this->validate();

        $team = Team::forceCreate([
            'user_id'       => Filament::auth()->id(),
            'name'          => $this->data['name'] ?? '',
            'personal_team' => false,
        ]);

        $this->user()->teams()->attach($team, ['role' => 'admin']);
        $this->user()->switchTeam($team);

        $this->redirect(route('filament.pages.edit-team', ['team' => $team]));
    }

    #[\Override]
    public function getBreadcrumbs(): array
    {
        return [url()->current() => 'Edit Team'];
    }

    private function user(): User
    {
        return Filament::auth()->user();
    }
}
