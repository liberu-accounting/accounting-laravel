<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function currencies(): array
    {
        return [
            Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]),
            Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false]),
        ];
    }

    public function test_update_exchange_rates_persists_correct_currency_ids(): void
    {
        config()->set('services.exchange_rate_api', ['key' => 'test', 'url' => 'https://fx.test/latest']);
        [$usd, $eur] = $this->currencies();

        Http::fake(['fx.test/*' => Http::response(['data' => ['EUR' => 0.92]], 200)]);

        app(ExchangeRateService::class)->updateExchangeRates();

        $this->assertDatabaseHas('exchange_rates', [
            'from_currency_id' => $usd->currency_id,
            'to_currency_id' => $eur->currency_id,
            'rate' => 0.92,
        ]);
    }

    public function test_get_exchange_rate_returns_stored_rate(): void
    {
        [$usd, $eur] = $this->currencies();
        ExchangeRate::create([
            'from_currency_id' => $usd->currency_id,
            'to_currency_id' => $eur->currency_id,
            'rate' => 0.92,
            'date' => '2026-06-10',
        ]);

        $this->assertEquals(0.92, app(ExchangeRateService::class)->getExchangeRate($usd, $eur));
    }
}
