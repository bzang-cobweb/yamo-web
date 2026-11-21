<?php

namespace Modules\Offer\Lists;

use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;

/**
 * Le titre et la première ligne d'une carte d'offre, les mêmes à l'accueil, dans « Mes logements » et
 * dans « Mes réservations » (décision 2026-11-21-cartes-titre-saisi-ligne-type) : le titre saisi
 * (« Studio meublé à Bonanjo ») ; dessous, la ligne du type servie par le domaine (« Location meublée
 * · Studio · Douala »). Sans titre saisi, la ligne du type devient le titre.
 */
final class OfferCardTitle
{
    /**
     * @return array{0: string, 1: string|null} le titre, la ligne du type (`null` : aucune)
     */
    public static function of(Offer $offer, string $locale): array
    {
        $domain = app(DomainRegistry::class)->require();
        $typed = (string) $offer->getTranslation('title', $locale);
        $line = $domain->itemTypeLine($offer, $locale) ?? $domain->itemTitle($offer);
        $title = $typed !== '' ? $typed : ($line ?? '');

        return [$title, $line !== null && $line !== '' && $line !== $title ? $line : null];
    }

    /**
     * Les deux inversés, pour les cartes de l'accueil, de « Mes logements » et de « Mes réservations » (décision
     * 2026-11-21-cartes-titre-compose-en-tete) : en titre, la ligne du type (« Location meublée · Studio ·
     * Douala ») ; dessous, le titre saisi. Sans ligne du type, le titre saisi seul.
     *
     * @return array{0: string, 1: string|null} le titre de la carte, sa première ligne (`null` : aucune)
     */
    public static function composedFirst(Offer $offer, string $locale): array
    {
        [$title, $line] = self::of($offer, $locale);

        return $line === null ? [$title, null] : [$line, $title];
    }
}
