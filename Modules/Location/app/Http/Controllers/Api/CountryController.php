<?php

namespace Modules\Location\Http\Controllers\Api;

use Collator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Modules\Location\Http\Resources\CountryResource;
use Modules\Location\Models\Country;

/**
 * Pays proposés à l'entrée de l'app, avant toute connexion (décision
 * 2026-11-18-espace-pays).
 */
class CountryController
{
    /**
     * Les pays actifs, triés par nom dans la langue de la requête. Un seul : l'app
     * y entre sans demander.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        $collator = new Collator(App::getLocale());

        return CountryResource::collection(Country::query()->active()->get()
            ->sort(fn (Country $a, Country $b): int => (int) $collator->compare((string) $a->getTranslation('name', App::getLocale()), (string) $b->getTranslation('name', App::getLocale())))
            ->values());
    }
}
