<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * Shared OAuth 2.0 token request for the provider sync services. POSTs the
 * grant (form-encoded, HTTP Basic client auth) to the configured token URL.
 */
trait RequestsProviderTokens
{
    /**
     * @param  array<string, mixed>  $cfg  the provider's services.* config
     * @param  array<string, string>  $form  grant params (grant_type, code/refresh_token, …)
     * @return array<string, mixed>
     */
    protected function requestProviderTokens(array $cfg, array $form): array
    {
        return Http::asForm()
            ->withBasicAuth($cfg['client_id'], $cfg['client_secret'])
            ->post($cfg['token_url'], $form)
            ->throw()
            ->json();
    }
}
