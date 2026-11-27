<?php

namespace Bza\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * The single place where the package reads the requesting account, through the guard chosen in `form.guard`
 * (Laravel's default guard when `null`), and whether visitors may upload files (decision
 * 2026-11-27-paquet-form-acces). The package imposes no guard of its own.
 */
final class FormAuth
{
    /** The configured guard name, or `null` for Laravel's default guard. */
    public static function guard(): ?string
    {
        $guard = config('form.guard');

        return is_string($guard) && $guard !== '' ? $guard : null;
    }

    /** The requesting account, or `null` for a visitor. */
    public static function user(): ?Authenticatable
    {
        return Auth::guard(self::guard())->user();
    }

    /** The requesting account's identifier, or `null` for a visitor. */
    public static function id(): int|string|null
    {
        return Auth::guard(self::guard())->id();
    }

    /** May a visitor without an account upload files? */
    public static function guestsMayUpload(): bool
    {
        return (bool) config('form.uploads.guests', false);
    }

    /**
     * The middleware of the upload route: the configured ones, plus `auth` on the configured guard unless visitors
     * may upload.
     *
     * @return list<string>
     */
    public static function uploadMiddleware(): array
    {
        $middleware = array_values((array) config('form.uploads.middleware', []));

        $guard = self::guard();

        return self::guestsMayUpload() ? $middleware : ['auth'.($guard === null ? '' : ':'.$guard), ...$middleware];
    }
}
