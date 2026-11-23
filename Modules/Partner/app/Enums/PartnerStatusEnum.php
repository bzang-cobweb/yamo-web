<?php

namespace Modules\Partner\Enums;

/**
 * La publication d'un partenaire (décision 2026-11-24-partenaires-statuts) : en validation (proposé ou
 * modifié depuis l'app), publié (affiché, selon sa campagne), refusé par l'équipe, archivé.
 */
enum PartnerStatusEnum: string
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return __('partner::partner.statuses.'.$this->value);
    }

    /**
     * Ton de la pastille dans « Mes partenaires » : en attente orange, publié vert, refusé rouge, archivé gris.
     */
    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
            self::ARCHIVED => 'neutral',
        };
    }

    /** Couleur du badge au back-office. */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
            self::ARCHIVED => 'gray',
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
