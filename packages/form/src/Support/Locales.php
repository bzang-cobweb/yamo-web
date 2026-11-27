<?php

namespace Bza\Form\Support;

use Illuminate\Support\Str;
use Locale;

/**
 * The languages offered by the application: `config('form.locales')`, failing that
 * `config('app.supported_locales')`, failing that `app.locale` alone (decision 2026-11-27-paquet-form).
 */
final class Locales
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_values((array) (config('form.locales') ?? config('app.supported_locales') ?? [config('app.locale')]));
    }

    /**
     * Default language of the application — not `config('app.locale')`, which
     * App::setLocale() replaces with the request's language.
     */
    public static function default(): string
    {
        return (string) (config('form.default_locale') ?? config('app.default_locale') ?? config('app.locale'));
    }

    /**
     * Language codes and names, each name written in its own language
     * (« Français », « English »): that is how people recognise their own.
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
