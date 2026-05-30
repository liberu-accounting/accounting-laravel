<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use Illuminate\Http\Response;
use Laravel\Socialite\Two\InvalidStateException;

class HandleInvalidState
{
    public function handle(InvalidStateException $exception): Response
    {
        throw new \RuntimeException('Socialstream support removed');
    }
}
