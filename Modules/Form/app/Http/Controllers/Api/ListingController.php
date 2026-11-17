<?php

namespace Modules\Form\Http\Controllers\Api;

use Modules\Form\Http\Resources\ListingResource;
use Modules\Form\Support\Listing;

/**
 * Listes affichées par l'app mobile (décision 2026-11-18-adresses-liste-generique).
 */
class ListingController
{
    /**
     * Une liste : titre, texte quand elle est vide, bouton de création, items
     * (titre, sous-titre, formulaire d'édition, retrait). Liste inconnue : 404 ;
     * demandée sans jeton : 401.
     */
    public function show(Listing $list): ListingResource
    {
        return new ListingResource($list);
    }
}
