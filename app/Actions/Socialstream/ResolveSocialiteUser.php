<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use Laravel\Socialite\Contracts\User;

class ResolveSocialiteUser
{
    /**
     * Resolve the user for a given provider.
     */
    public function resolve(string $provider): User
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
