<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
}
