<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function currencies(): array
    {
        $usd = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]);
        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false]);

        return [$usd, $eur];
    }

    public function test_get_latest_rates_returns_one_row_per_pair(): void
    {
        [$usd, $eur] = $this->currencies();

        ExchangeRate::create(['from_currency_id' => $usd->currency_id, 'to_currency_id' => $eur->currency_id, 'rate' => 0.90, 'date' => '2026-06-01']);
        ExchangeRate::create(['from_currency_id' => $usd->currency_id, 'to_currency_id' => $eur->currency_id, 'rate' => 0.92, 'date' => '2026-06-10']);

        $rates = app(ExchangeRateService::class)->getLatestRates();

        $this->assertCount(1, $rates);
        $this->assertSame('USD', $rates[0]['from']);
        $this->assertSame('EUR', $rates[0]['to']);
        $this->assertEquals(0.92, $rates[0]['rate']); // latest by date
    }

    public function test_exchange_rates_endpoint_returns_ok(): void
    {
        [$usd, $eur] = $this->currencies();
        ExchangeRate::create(['from_currency_id' => $usd->currency_id, 'to_currency_id' => $eur->currency_id, 'rate' => 0.92, 'date' => '2026-06-10']);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/exchange-rates')
            ->assertOk()
            ->assertJsonFragment(['from' => 'USD', 'to' => 'EUR']);
    }
}
