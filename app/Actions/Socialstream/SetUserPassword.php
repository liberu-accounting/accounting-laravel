<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

class SetUserPassword
{
    public function set(mixed $user, array $input): void
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
