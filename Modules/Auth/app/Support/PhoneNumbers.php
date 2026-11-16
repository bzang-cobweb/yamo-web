<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Facades\App;
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
     * Pays actifs proposés par un champ téléphone : code ISO => libellé et indicatif.
     *
     * @return array<string, array{label: string, hint: string}>
     */
    public static function countryOptions(): array
    {
        $collator = new \Collator(App::getLocale());

        return Country::query()->active()->get()
            ->sort(fn (Country $a, Country $b): int => (int) $collator->compare($a->label(), $b->label()))
            ->mapWithKeys(fn (Country $country): array => [$country->code => ['label' => $country->label(), 'hint' => $country->dial_code]])
            ->all();
    }

    /**
     * Identifiant de connexion saisi en format international (`+41 79…`, `0041…`)
     * → E.164, ou `null` si ce n'en est pas un.
     */
    public static function fromInternational(string $value): ?string
    {
        $value = preg_replace('/^00/', '+', preg_replace('/[\s.\-()]/', '', $value) ?? '') ?? '';

        if (! str_starts_with($value, '+')) {
            return null;
        }

        try {
            $phone = new PhoneNumber($value);

            return $phone->isValid() ? $phone->formatE164() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pays actif d'après son code ISO, ou `null`.
     */
    public static function country(?string $code): ?Country
    {
        return $code === null ? null : Country::query()->active()->where('code', strtoupper($code))->first();
    }
}
