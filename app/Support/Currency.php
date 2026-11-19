<?php

namespace App\Support;

use NumberFormatter;

/**
 * Une devise telle qu'une personne la lit (décision
 * 2026-11-19-contrat-formulaire-etapes-symbole-devise) : son symbole local, dans
 * la langue donnée, jamais son code ISO — XAF → « FCFA », EUR → « € ». Le code
 * reste ce qui est enregistré et échangé par l'API. Tout écran qui affiche une
 * devise passe par ici, ou par `Number::currency()` qui fait de même.
 */
final class Currency
{
    /**
     * Le symbole de la devise `$code` (ISO 4217) dans `$locale` (langue courante
     * par défaut) ; le code tel quel si ICU n'en connaît pas, `null` sans code.
     */
    public static function symbol(?string $code, ?string $locale = null): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $code = strtoupper(trim($code));
        $formatter = new NumberFormatter(($locale ?? app()->getLocale()).'@currency='.$code, NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return is_string($symbol) && $symbol !== '' ? $symbol : $code;
    }
}
