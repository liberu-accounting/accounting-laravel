<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SocialstreamRegistrationTest extends TestCase
{
    public function test_socialstream_config_has_social_media_providers(): void
    {
        $providers = config('socialstream.providers', []);

        $this->assertNotEmpty($providers, 'socialstream.providers must not be empty');

        $providerIds = array_map(
            fn($p) => is_object($p) ? $p->getId() : (string) $p,
            $providers,
        );

        $expected = [
            'bitbucket',
            'facebook',
            'github',
            'gitlab',
            'google',
            'linkedin',
            'linkedin-openid',
            'slack',
            'twitter-oauth-2',
        ];

        foreach ($expected as $provider) {
            $this->assertContains(
                $provider,
                $providerIds,
                "Provider '{$provider}' is missing from socialstream.providers config",
            );
        }
    }

    public function test_socialstream_config_excludes_twitter_oauth1(): void
    {
        $providers = config('socialstream.providers', []);

        $providerIds = array_map(
            fn($p) => is_object($p) ? $p->getId() : (string) $p,
            $providers,
        );

        $this->assertNotContains(
            'twitter',
            $providerIds,
            'OAuth 1.0 twitter provider must not be configured (requires live API keys)',
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('oauthProviderDataProvider')]
    public function test_oauth_services_config_has_credentials_keys(string $provider): void
    {
        $this->assertArrayHasKey($provider, config('services'));
    }

    public static function oauthProviderDataProvider(): array
    {
        return [
            'github'          => ['github'],
            'google'          => ['google'],
            'facebook'        => ['facebook'],
            'gitlab'          => ['gitlab'],
            'bitbucket'       => ['bitbucket'],
            'linkedin'        => ['linkedin'],
            'linkedin-openid' => ['linkedin-openid'],
            'slack'           => ['slack'],
            'twitter-oauth-2' => ['twitter-oauth-2'],
        ];
    }
}
