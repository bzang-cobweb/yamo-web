<?php

namespace Modules\Partner\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;

/**
 * Les notifications des partenaires dans la liste « Notifications » (décisions
 * 2026-11-24-partenaires-notifications-filtre et 2026-11-24-partenaire-notification-par-statut) :
 * - le filtre « Type » : « Partenaire », toutes ses notifications, une classe par statut (décision
 *   2026-11-24-notifications-types-reservation-partenaire) ;
 * - pour ces types, le filtre « Statut » commun : le statut que la notification annonce (sa classe) ;
 * - la pastille du statut notifié sur chaque carte, et son titre dans la langue du lecteur ;
 * - la recherche, dans le nom du partenaire.
 */
class PartnerNotificationFilter implements NotificationFilter
{
    /** La clé du groupe « Partenaire » dans le filtre « Type » : tous les statuts. */
    public const PARTNER = 'partner';

    public function types(): array
    {
        return [self::PARTNER => ['label' => __('partner::partner.notify.filter_type'), 'types' => PartnerStatusEnum::notifications()]];
    }

    /** La clé d'un statut dans le filtre « Statut » (`partner_published`). */
    public static function status(PartnerStatusEnum $status): string
    {
        return self::PARTNER.'_'.$status->value;
    }

    /** La pastille du statut notifié (« En validation », « Publié »…). */
    public function tag(DatabaseNotification $notification): ?array
    {
        $status = PartnerStatusEnum::fromNotification($notification->getAttribute('type'));
        if ($status === null) {
            return null;
        }

        return ['label' => $status->label(), 'tone' => $status->tone()];
    }

    /** L'étape d'une demande dans son suivi (« Partenaire modifié », « Partenaire accepté »), dans la langue du lecteur. */
    public function step(DatabaseNotification $notification, User $reader): ?string
    {
        return $this->cardTitle($notification, $reader);
    }

    /** « Partenaire accepté », « Partenaire modifié »…, dans la langue du lecteur ; `null` pour une autre notification. */
    public function cardTitle(DatabaseNotification $notification, User $reader): ?string
    {
        $status = PartnerStatusEnum::fromNotification($notification->getAttribute('type'));
        if ($status === null) {
            return null;
        }

        $data = (array) $notification->data;
        $action = $data['action'] ?? null;
        // Une ancienne notification : `created` (vrai : ajouté, faux : modifié).
        if (! is_string($action) && is_bool($data['created'] ?? null)) {
            $action = $data['created'] ? PartnerNotification::CREATED : PartnerNotification::UPDATED;
        }

        return PartnerNotification::title($status, is_string($action) ? $action : null);
    }

    /**
     * Une notification du fil d'un partenaire que le lecteur n'a pas reçue (sa propre action, ou celle d'un autre de
     * ses comptes) : rédigée pour lui, dans sa langue, à partir du partenaire (décision
     * 2026-11-24-notifications-fil-de-groupe) ; `null` si le partenaire n'existe plus.
     */
    public function forReader(DatabaseNotification $notification, User $reader): ?array
    {
        $status = PartnerStatusEnum::fromNotification($notification->getAttribute('type'));
        if ($status === null) {
            return null;
        }

        $data = (array) $notification->data;
        $partner = Partner::query()->find($data['partner_id'] ?? null);
        if ($partner === null) {
            return null;
        }

        $actorId = $notification->getAttribute('user_id');
        $actor = $actorId === null ? null : User::query()->find($actorId);
        $action = $data['action'] ?? null;

        return PartnerNotification::for($partner, $status, is_string($action) ? $action : null, $actor?->getKey(), (string) $actor?->full_name)->toArray($reader);
    }

    public function prepare(array $notifications): void {}

    /**
     * Les statuts d'un partenaire, dans le filtre « Statut » commun, pour les types d'un partenaire : le statut que la
     * notification annonce (décision 2026-11-24-notifications-types-groupes-statut).
     */
    public function statuses(): array
    {
        $statuses = [];
        foreach (PartnerStatusEnum::cases() as $status) {
            $statuses[self::status($status)] = ['label' => $status->label(), 'parent' => self::PARTNER];
        }

        return $statuses;
    }

    public function fields(User $user): array
    {
        return [];
    }

    /** « Statut » : les notifications de ce statut, sa classe ; rien pour un statut d'un autre module. */
    public function apply(Builder $query, array $filters, User $user): void
    {
        foreach (PartnerStatusEnum::cases() as $status) {
            if (($filters['status'] ?? null) === self::status($status)) {
                $query->where('type', $status->notification());

                return;
            }
        }
    }

    public function orSearch(Builder $query, string $term): void
    {
        $ids = Partner::query()
            ->where(fn (Builder $names) => ContainsSearch::orWhere($names, array_map(fn (string $locale): string => "name->{$locale}", Locales::values()), $term))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($ids !== []) {
            $query->orWhereIn('data->partner_id', $ids);
        }
    }
}
