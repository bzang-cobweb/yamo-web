<?php

namespace Modules\Location\Support;

use Illuminate\Validation\ValidationException;
use Modules\Location\Models\Country;

/**
 * Le pays courant de l'app, son « espace » (décision 2026-11-18-espace-pays) :
 * l'en-tête `X-Country` (code ISO d'un pays actif) ; à défaut, le seul pays
 * actif s'il n'y en a qu'un ; sinon aucun. Résolu une fois par requête.
 */
final class CurrentCountry
{
    public const HEADER = 'X-Country';

    private const CACHE = 'location.current_country';

    public static function get(): ?Country
    {
        $request = request();

        if ($request->attributes->has(self::CACHE)) {
            return $request->attributes->get(self::CACHE);
        }

        $code = strtoupper(trim((string) $request->header(self::HEADER)));
        $country = $code === '' ? null : Country::query()->active()->where('code', $code)->first();

        if ($country === null) {
            $active = Country::query()->active()->limit(2)->get();
            $country = $active->count() === 1 ? $active->first() : null;
        }

        $request->attributes->set(self::CACHE, $country);

        return $country;
    }

    /**
     * Le pays courant, quand ce qui est demandé en dépend : sans lui, 422.
     *
     * @throws ValidationException
     */
    public static function require(): Country
    {
        return self::get() ?? throw ValidationException::withMessages(['country' => __('location::country.required')]);
    }
}
