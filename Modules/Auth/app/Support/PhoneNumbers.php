<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Facades\App;
use Modules\Location\Models\Country;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Numéros de téléphone : un seul domicile pour leur forme enregistrée (E.164,
 * `+41791234567`) et leurs affichages, national et international. Le pays donne
 * l'indicatif.
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
     * E.164 → numéro international affiché (« +41 79 123 45 67 »).
     */
    public static function international(string $e164): string
    {
        return (new PhoneNumber($e164))->formatInternational();
    }

    /**
     * Pays proposés par un champ téléphone : tous, pas seulement les actifs (décision
     * 2026-11-28-connexion-panneau) ; code ISO => libellé et indicatif. Les pays actifs ne servent qu'au choix du
     * pays courant.
     *
     * @return array<string, array{label: string, hint: string}>
     */
    public static function countryOptions(): array
    {
        $collator = new \Collator(App::getLocale());

        return Country::query()->get()
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
     * Pays d'un indicatif d'après son code ISO, actif ou non (tous sont proposés aux champs téléphone), ou `null`.
     */
    public static function country(?string $code): ?Country
    {
        return $code === null ? null : Country::query()->where('code', strtoupper($code))->first();
    }
}
