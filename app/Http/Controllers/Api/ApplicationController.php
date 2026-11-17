<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApplicationResource;
use App\Models\Setting;

/**
 * Présentation de l'application, pour la page d'accueil.
 */
class ApplicationController
{
    /**
     * Nom, logo et description de l'application, dans la langue du compte
     * connecté, sinon celle de `Accept-Language`.
     *
     * @unauthenticated
     */
    public function show(): ApplicationResource
    {
        return new ApplicationResource(Setting::current());
    }
}
