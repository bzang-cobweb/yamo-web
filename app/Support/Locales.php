<?php

namespace App\Support;

use Illuminate\Support\Str;
use Locale;

/**
 * Les langues proposées par l'application : une seule source,
 * `config('app.supported_locales')`.
 */
final class Locales
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_values(config('app.supported_locales'));
    }

    /**
     * Codes et noms de langue, chaque nom écrit dans sa propre langue
     * (« Français », « English ») : c'est ainsi qu'on reconnaît la sienne.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::values())
            ->mapWithKeys(fn (string $code): array => [$code => self::label($code)])
            ->all();
    }

    public static function label(string $code): string
    {
        return Str::ucfirst(Locale::getDisplayLanguage($code, $code));
    }
}
