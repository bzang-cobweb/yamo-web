<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests ne dépendent pas de la liste des langues du projet : français et
        // anglais ; un test qui porte sur une autre langue la déclare lui-même
        // (décision 2026-11-19-contrat-langue-saisie-allemand).
        config(['app.supported_locales' => ['fr', 'en']]);
    }

    /**
     * Une région du pays, la même à chaque appel, créée au besoin : une adresse n'a plus de pays, sa région le donne
     * (décision 2026-11-25-adresse-sans-pays).
     */
    protected function regionOf(Country|int $country): int
    {
        $id = $country instanceof Country ? (int) $country->getKey() : $country;

        return (int) (Region::query()->where('country_id', $id)->orderBy('id')->value('id') ?? Region::factory()->create(['country_id' => $id])->getKey());
    }
}
