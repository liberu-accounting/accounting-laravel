<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use App\Models\User;

class CreateUserFromProvider
{
    public function create(string $provider, $providerUser): User
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
