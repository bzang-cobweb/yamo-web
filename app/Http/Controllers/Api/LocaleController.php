<?php

namespace App\Http\Controllers\Api;

use Bza\Form\Support\Locales;
use Illuminate\Http\JsonResponse;

/**
 * Langues proposées par l'application (`config('app.supported_locales')`).
 */
class LocaleController
{
    /**
     * Langues disponibles, chacune nommée dans sa propre langue. L'app mobile
     * choisit parmi elles la meilleure langue de l'appareil.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => collect(Locales::options())
                ->map(fn (string $label, string $code): array => ['code' => $code, 'label' => $label])
                ->values()
                ->all(),
            /** Langue par défaut, quand aucune langue de l'appareil n'est disponible. */
            'default' => Locales::default(),
        ]);
    }
}
