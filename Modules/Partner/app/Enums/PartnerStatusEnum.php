<?php

namespace Modules\Partner\Enums;

use Modules\Partner\Notifications\PartnerAwaitingPayment;
use Modules\Partner\Notifications\PartnerNotification;
use Modules\Partner\Notifications\PartnerPending;
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Notifications\PartnerRejected;

/**
 * La publication d'un partenaire (décision 2026-11-24-partenaires-statuts) : en validation (proposé ou
 * modifié depuis l'app), en attente de paiement (décision 2026-11-24-partenaire-attente-paiement), publié (affiché,
 * selon sa campagne), refusé par l'équipe ; plus d'« archivé » (décision 2026-11-24-partenaire-sans-archive). Seul un
 * partenaire publié peut être affiché.
 */
enum PartnerStatusEnum: string
{
    case PENDING = 'pending';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return __('partner::partner.statuses.'.$this->value);
    }

    /**
     * Ton de la pastille dans « Mes partenaires » : en attente (validation, paiement) orange, publié vert, refusé rouge.
     */
    public function tone(): string
    {
        return match ($this) {
            self::PENDING, self::AWAITING_PAYMENT => 'warning',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /** Couleur du badge au back-office : l'attente de paiement en bleu, pour la distinguer de la validation. */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::AWAITING_PAYMENT => 'info',
            self::PUBLISHED => 'success',
            self::REJECTED => 'danger',
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
            self::AWAITING_PAYMENT => PartnerAwaitingPayment::class,
            self::PUBLISHED => PartnerPublished::class,
            self::REJECTED => PartnerRejected::class,
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
