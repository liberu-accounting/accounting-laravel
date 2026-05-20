<?php

namespace App\Actions\Socialstream;

use App\Models\ConnectedAccount;

class CreateConnectedAccount
{
    public function create(mixed $user, string $provider, $providerUser): ConnectedAccount
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
