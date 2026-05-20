<?php

namespace App\Actions\Socialstream;

use App\Models\User;

class CreateUserWithTeamsFromProvider
{
    public function __construct(...$args)
    {
    }

    public function create(string $provider, $providerUser): User
    {
        throw new \RuntimeException('Socialstream support removed');
    }

    protected function createTeam(User $user): void
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
