<?php

namespace Modules\Partner\Enums;

use Modules\Partner\Notifications\PartnerArchived;
use Modules\Partner\Notifications\PartnerNotification;
use Modules\Partner\Notifications\PartnerPending;
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Notifications\PartnerRejected;

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

    /**
     * La classe de notification du statut (décision 2026-11-24-partenaire-notification-par-statut).
     *
     * @return class-string<PartnerNotification>
     */
    public function notification(): string
    {
        return match ($this) {
            self::PENDING => PartnerPending::class,
            self::PUBLISHED => PartnerPublished::class,
            self::REJECTED => PartnerRejected::class,
            self::ARCHIVED => PartnerArchived::class,
        };
    }

    /**
     * Les classes de notification d'un partenaire, tous statuts.
     *
     * @return list<class-string<PartnerNotification>>
     */
    public static function notifications(): array
    {
        return array_map(fn (self $status): string => $status->notification(), self::cases());
    }

    /** Le statut d'une classe de notification ; `null` pour une autre. */
    public static function fromNotification(mixed $type): ?self
    {
        foreach (self::cases() as $status) {
            if ($status->notification() === $type) {
                return $status;
            }
        }

        return null;
    }
}
