<?php

namespace App\Actions\Socialstream;

use App\Models\User;

class CreateUserFromProvider
{
    public function __construct(...$args)
    {
    }

    public function create(string $provider, $providerUser): User
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
