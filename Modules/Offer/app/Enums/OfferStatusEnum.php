<?php

namespace Modules\Offer\Enums;

/**
 * Cycle de vie d'une offre : brouillon, publiée (visible), archivée.
 */
enum OfferStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return __('offer::offer.statuses.'.$this->value);
    }

    /**
     * Ton de la pastille dans « Mes logements » (décision 2026-11-19-statut-carte-logement) :
     * publiée verte, brouillon orange, archivée grise.
     */
    public function tone(): string
    {
        return match ($this) {
            self::PUBLISHED => 'success',
            self::DRAFT => 'warning',
            self::ARCHIVED => 'neutral',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
