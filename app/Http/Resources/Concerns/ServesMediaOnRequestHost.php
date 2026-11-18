<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * Adresse d'un fichier servie sur l'hôte de la requête (même règle que
 * `App\Support\MediaUrl`, pour une requête donnée).
 */
trait ServesMediaOnRequestHost
{
    protected function onRequestHost(?string $url, Request $request): ?string
    {
        return $url === null ? null : $request->getSchemeAndHttpHost().parse_url($url, PHP_URL_PATH);
    }
}
