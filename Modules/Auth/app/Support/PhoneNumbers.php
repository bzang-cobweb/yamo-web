<?php

namespace Modules\Auth\Support;

use Modules\Location\Models\Country;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Numéros de téléphone : un seul domicile pour leur forme enregistrée (E.164,
 * `+41791234567`) et leur affichage national. Le pays donne l'indicatif.
 */
final class PhoneNumbers
{
    /**
     * Numéro déjà validé (`phone` de laravel-phone) → E.164.
     */
    public static function toE164(string $number, string $countryCode): string
    {
        return (new PhoneNumber($number, $countryCode))->formatE164();
    }

    /**
     * E.164 → numéro national affiché (« 079 123 45 67 »), sans l'indicatif.
     */
    public static function national(string $e164): string
    {
        return (new PhoneNumber($e164))->formatNational();
    }

    /**
     * Pays actif d'après son code ISO, ou `null`.
     */
    public static function country(?string $code): ?Country
    {
        return $code === null ? null : Country::query()->active()->where('code', strtoupper($code))->first();
    }
}
