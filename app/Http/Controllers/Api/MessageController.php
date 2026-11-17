<?php

namespace App\Http\Controllers\Api;

use App\Support\MobileMessages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

/**
 * Textes de l'interface de l'app mobile.
 */
class MessageController
{
    /**
     * Tous les textes de l'app, dans la langue du compte connecté, sinon celle de
     * `Accept-Language`. Variables au format `:name`.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $locale = App::getLocale();

        return response()->json([
            'data' => [
                /** Langue des textes renvoyés. */
                'locale' => $locale,
                /** Textes par clé (`form.stepOf`). */
                'messages' => MobileMessages::for($locale),
            ],
        ]);
    }
}
