<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Messages d'erreur de l'API dans la langue de la requête. Laravel rend en
 * anglais, sans `__()`, les messages par défaut des refus : « Unauthenticated. »
 * (jeton absent ou révoqué), « Unauthorized » (refus en 401) et « This action is
 * unauthorized. » (refus en 403). Seuls ces messages par défaut sont remplacés :
 * un message propre à une policy est rendu tel quel.
 */
final class TranslatedApiErrors
{
    private const DEFAULT_FORBIDDEN = 'This action is unauthorized.';

    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            return self::isApi($request)
                ? response()->json(['message' => __('api.unauthenticated')], Response::HTTP_UNAUTHORIZED)
                : null;
        });

        $exceptions->render(function (HttpException $exception, Request $request): ?JsonResponse {
            $message = self::translate($exception);

            return self::isApi($request) && $message !== null
                ? response()->json(['message' => $message], $exception->getStatusCode(), $exception->getHeaders())
                : null;
        });
    }

    /**
     * Le message traduit d'un refus par défaut, ou `null` pour laisser Laravel rendre
     * l'exception comme d'habitude.
     */
    private static function translate(HttpException $exception): ?string
    {
        $status = $exception->getStatusCode();
        $message = $exception->getMessage();
        $isDefault = $message === '' || $message === (Response::$statusTexts[$status] ?? null) || $message === self::DEFAULT_FORBIDDEN;

        return match (true) {
            ! $isDefault => null,
            $status === Response::HTTP_UNAUTHORIZED => __('api.unauthenticated'),
            $status === Response::HTTP_FORBIDDEN => __('api.forbidden'),
            default => null,
        };
    }

    private static function isApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
