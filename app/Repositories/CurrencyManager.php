<?php

namespace App\Repositories;

use App\Interfaces\CurrencyManager as CurrencyManagerInterface;
use App\Models\Currency;

class CurrencyManager implements CurrencyManagerInterface
{
    public function store(array $data)
    {
        $currency = new Currency;

        $currency->fill($data);

        $currency->save();

        return $currency;
    }

    public function update(Currency $currency, array $data)
    {
        $currency->fill($data);

        $currency->save();

        return $currency;
    }

    public function delete(Currency $currency)
    {
        $currency->delete();

        return $currency;
    }

    public function enable(Currency $currency): Currency
    {
        $this->disableAllCurrencies();

        $currency->is_enabled = true;

        $currency->save();

        return $currency;
    }

    private function disableAllCurrencies()
    {
        Currency::all()->each(function ($c) {
            $c->is_enabled = false;
            $c->save();
        });
    }

    public function enabledCurrency(): Currency
    {
        $enabledCurrency = Currency::where('is_enabled', true)->first();

        if (!$enabledCurrency) {
            return $this->defaultCurrency();
        }

        return $enabledCurrency;
    }

    public function defaultCurrency()
    {
        return new Currency([
            'name' => 'United States Dollar',
            'currency_code' => 'USD',
            'symbol' => '$'
        ]);
    }
}
