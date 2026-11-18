<?php

namespace Modules\Location\Support;

use NumberFormatter;

/**
 * Devise d'un pays d'après les données ICU de l'extension intl (CM → XAF, CH → CHF).
 */
final class Currencies
{
    public static function forCountry(string $code): ?string
    {
        $currency = (new NumberFormatter('en_'.strtoupper($code), NumberFormatter::CURRENCY))->getTextAttribute(NumberFormatter::CURRENCY_CODE);

        // ICU rend « XXX » (aucune devise) pour une région qu'il ne connaît pas.
        return is_string($currency) && preg_match('/^[A-Z]{3}$/', $currency) === 1 && $currency !== 'XXX' ? $currency : null;
    }
}
