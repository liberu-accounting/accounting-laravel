<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use App\Models\User;

class CreateUserWithTeamsFromProvider
{
    public function create(string $provider, $providerUser): User
    {
        throw new \RuntimeException('Socialstream support removed');
    }

    protected function createTeam(User $user): void
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
