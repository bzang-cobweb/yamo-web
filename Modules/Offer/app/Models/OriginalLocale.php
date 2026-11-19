<?php

namespace Modules\Offer\Models;

/**
 * Le repli d'un texte traduit vers la langue d'origine de son offre (décision
 * 2026-11-19-contrat-langue-saisie-allemand) : la langue demandée si le texte y
 * existe, sinon la langue d'origine si le texte y existe ; sinon `null`, et le repli
 * habituel (langue par défaut, puis une autre) s'applique.
 */
final class OriginalLocale
{
    /**
     * @param  list<string>  $translated  langues où le texte existe
     */
    public static function pick(array $translated, string $locale, ?string $original): ?string
    {
        if (in_array($locale, $translated, true)) {
            return $locale;
        }

        return $original !== null && in_array($original, $translated, true) ? $original : null;
    }
}
