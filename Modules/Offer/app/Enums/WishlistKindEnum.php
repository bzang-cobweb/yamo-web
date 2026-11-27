<?php

namespace Modules\Offer\Enums;

/**
 * La sorte d'une liste de favoris (décision 2026-11-27-listes-de-favoris) : une liste nommée par le membre, ou sa liste
 * « Récemment consultés », une seule par membre, remplie par l'app à l'ouverture d'une fiche.
 */
enum WishlistKindEnum: string
{
    case CUSTOM = 'custom';
    case RECENT = 'recent';
}
