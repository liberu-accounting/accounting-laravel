<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use App\Models\ConnectedAccount;

class UpdateConnectedAccount
{
    public function update(mixed $user, ConnectedAccount $connectedAccount, string $provider, $providerUser): ConnectedAccount
    {
        throw new \RuntimeException('Socialstream support removed');
    }

    public function updateRefreshToken(ConnectedAccount $connectedAccount): ConnectedAccount
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
