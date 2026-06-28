<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    private readonly string $apiKey;

    private readonly string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate_api.key') ?? '';
        $this->apiUrl = config('services.exchange_rate_api.url') ?? '';
    }

    public function updateExchangeRates(): void
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        $currencies = Currency::where('is_default', false)->get();

        foreach ($currencies as $currency) {
            $response = Http::get($this->apiUrl, [
                'apikey' => $this->apiKey,
                'base_currency' => $defaultCurrency->code,
                'currencies' => $currency->code,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rate = $data['data'][$currency->code];

                ExchangeRate::updateOrCreate(
                    [
                        'from_currency_id' => $defaultCurrency->id,
                        'to_currency_id' => $currency->id,
                        'date' => now()->toDateString(),
                    ],
                    ['rate' => $rate]
                );
            }
        }
    }

    /**
     * The most recent rate for each currency pair, newest first.
     *
     * @return list<array{from: ?string, to: ?string, rate: float, date: ?string}>
     */
    public function getLatestRates(): array
    {
        return ExchangeRate::with(['fromCurrency', 'toCurrency'])
            ->orderByDesc('date')
            ->get()
            ->unique(fn (ExchangeRate $r): string => $r->from_currency_id.'-'.$r->to_currency_id)
            ->map(fn (ExchangeRate $r): array => [
                'from' => $r->fromCurrency?->code,
                'to' => $r->toCurrency?->code,
                'rate' => (float) $r->rate,
                'date' => $r->date?->toDateString(),
            ])
            ->values()
            ->all();
    }

    public function getExchangeRate(Currency $fromCurrency, Currency $toCurrency): float|int|null
    {
        $exchangeRate = ExchangeRate::where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->latest('date')
            ->first();

        if (! $exchangeRate) {
            $this->updateExchangeRates();
            $exchangeRate = ExchangeRate::where('from_currency_id', $fromCurrency->id)
                ->where('to_currency_id', $toCurrency->id)
                ->latest('date')
                ->first();
        }

        return $exchangeRate?->rate;
    }
}
