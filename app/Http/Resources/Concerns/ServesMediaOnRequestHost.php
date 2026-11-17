<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * Adresse d'un fichier servie sur l'hôte de la requête : l'app joint l'API par
 * l'adresse qu'elle connaît (en local, HTTP), quel que soit `APP_URL`.
 */
trait ServesMediaOnRequestHost
{
    protected function onRequestHost(?string $url, Request $request): ?string
    {
        return $url === null ? null : $request->getSchemeAndHttpHost().parse_url($url, PHP_URL_PATH);
    }
}
