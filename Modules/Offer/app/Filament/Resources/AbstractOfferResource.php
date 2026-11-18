<?php

namespace Modules\Offer\Filament\Resources;

use App\Filament\AbstractResource;
use Modules\Offer\Domains\DomainRegistry;

/**
 * Base des ressources du module Offer : leur groupe de navigation, et aucun accès
 * sans domaine choisi (décision 2026-11-18-offres-et-domaines).
 */
abstract class AbstractOfferResource extends AbstractResource
{
    public static function getNavigationGroup(): ?string
    {
        return __('offer::offer.navigation_group');
    }

    public static function canAccess(): bool
    {
        return app(DomainRegistry::class)->active() !== null && parent::canAccess();
    }
}
