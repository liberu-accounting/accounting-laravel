<?php

namespace App\Actions\Socialstream;

use Symfony\Component\HttpFoundation\RedirectResponse;

class GenerateRedirectForProvider
{
    public function generate(string $provider): RedirectResponse
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
