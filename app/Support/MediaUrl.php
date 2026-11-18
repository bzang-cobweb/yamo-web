<?php

namespace App\Support;

/**
 * Adresse d'un fichier servie sur l'hôte de la requête : l'app joint l'API par
 * l'adresse qu'elle connaît (en local, HTTP), quel que soit `APP_URL`. Seul
 * domicile de cette règle : ressources d'API (`ServesMediaOnRequestHost`) et
 * photos des listes (décision 2026-11-18-liste-photo-barre-connecte).
 */
final class MediaUrl
{
    public static function onRequestHost(?string $url): ?string
    {
        return $url === null ? null : request()->getSchemeAndHttpHost().parse_url($url, PHP_URL_PATH);
    }
}
