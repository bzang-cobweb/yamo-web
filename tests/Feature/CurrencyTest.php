<?php

namespace Tests\Feature;

use App\Support\Currency;
use Tests\TestCase;

/**
 * Le symbole local d'une devise, jamais son code (décision
 * 2026-11-19-contrat-formulaire-etapes-symbole-devise).
 */
class CurrencyTest extends TestCase
{
    public function test_a_currency_shows_its_local_symbol(): void
    {
        $this->assertSame('FCFA', Currency::symbol('XAF', 'fr'));
        $this->assertSame('FCFA', Currency::symbol('xaf', 'en'));
        $this->assertSame('€', Currency::symbol('EUR', 'fr'));
        $this->assertSame('CHF', Currency::symbol('CHF', 'fr'));
        $this->assertSame('$US', Currency::symbol('USD', 'fr'));
        $this->assertNull(Currency::symbol(null));
        $this->assertNull(Currency::symbol(' '));
    }

    public function test_the_current_language_by_default(): void
    {
        app()->setLocale('en');

        $this->assertSame('$', Currency::symbol('USD'));
    }
}
