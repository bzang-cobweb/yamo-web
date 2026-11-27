<?php

namespace Bza\Form\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Bza\Form\Http\Resources\ListingResource;
use Bza\Form\Support\Listing;

/**
 * Lists displayed by the mobile app (decision 2026-11-18-adresses-liste-generique).
 */
class ListingController
{
    /**
     * A list: title, text when it is empty, create button, items
     * (title, subtitle, edit form, removal), served in pages, restricted to the search if there is one.
     * Unknown list: 404; requested without a token: 401.
     */
    #[QueryParameter('cursor', description: 'Curseur de la page voulue, tel que servi dans `next_cursor` ; absent ou invalide : première page.', type: 'string', required: false)]
    #[QueryParameter('search', description: 'Texte cherché, pour une liste cherchable (`search` non nul) ; vide : toute la liste.', type: 'string', required: false)]
    public function show(Listing $list): ListingResource
    {
        return new ListingResource($list);
    }
}
