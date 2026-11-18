<?php

namespace Modules\Form\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Modules\Form\Http\Resources\ListingResource;
use Modules\Form\Support\Listing;

/**
 * Listes affichées par l'app mobile (décision 2026-11-18-adresses-liste-generique).
 */
class ListingController
{
    /**
     * Une liste : titre, texte quand elle est vide, bouton de création, items
     * (titre, sous-titre, formulaire d'édition, retrait), servis par pages.
     * Liste inconnue : 404 ; demandée sans jeton : 401.
     */
    #[QueryParameter('cursor', description: 'Curseur de la page voulue, tel que servi dans `next_cursor` ; absent ou invalide : première page.', type: 'string', required: false)]
    public function show(Listing $list): ListingResource
    {
        return new ListingResource($list);
    }
}
