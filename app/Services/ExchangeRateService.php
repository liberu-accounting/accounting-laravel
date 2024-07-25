<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate_api.key');
        $this->apiUrl = config('services.exchange_rate_api.url');
    }

    public function updateExchangeRates()
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

    public function getExchangeRate(Currency $fromCurrency, Currency $toCurrency)
    {
        $exchangeRate = ExchangeRate::where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->latest('date')
            ->first();

        if (!$exchangeRate) {
            $this->updateExchangeRates();
            $exchangeRate = ExchangeRate::where('from_currency_id', $fromCurrency->id)
                ->where('to_currency_id', $toCurrency->id)
                ->latest('date')
                ->first();
        }

        return $exchangeRate ? $exchangeRate->rate : null;
    }
}