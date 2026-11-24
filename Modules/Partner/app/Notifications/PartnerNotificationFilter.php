<?php

namespace Modules\Partner\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;

/**
 * Les notifications des partenaires dans la liste « Notifications » (décision
 * 2026-11-24-partenaires-notifications-filtre) :
 * - un type, « Partenaire » : ajout, modification, changement de statut ;
 * - pour ce type, le filtre « Statut du partenaire » : le statut que la notification annonce ;
 * - la pastille de ce statut sur chaque carte ;
 * - la recherche, dans le nom du partenaire.
 */
class PartnerNotificationFilter implements NotificationFilter
{
    /** La clé du type « Partenaire » dans le filtre « Type ». */
    public const PARTNER = 'partner';

    /** Les classes de ses notifications. */
    private const NOTIFICATIONS = [PartnerSubmittedNotification::class, PartnerStatusNotification::class];

    public function types(): array
    {
        return [self::PARTNER => ['label' => __('partner::partner.notify.filter_type'), 'types' => self::NOTIFICATIONS]];
    }

    /** La pastille du statut annoncé (« En validation », « Publié »…). */
    public function tag(DatabaseNotification $notification): ?array
    {
        $status = $this->status($notification);
        if ($status === null) {
            return null;
        }

        return ['label' => $status->label(), 'tone' => $status->tone()];
    }

    public function step(DatabaseNotification $notification): ?string
    {
        return null;
    }

    public function cardTitle(DatabaseNotification $notification): ?string
    {
        return null;
    }

    public function prepare(array $notifications): void {}

    public function fields(User $user): array
    {
        return [
            Field::select('partner_status')
                ->label(__('partner::partner.notify.filter_status'))
                ->options(PartnerStatusEnum::options())
                ->visibleWhen('type', [self::PARTNER]),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        $status = $filters['partner_status'] ?? null;
        if (! is_string($status)) {
            return;
        }
        if (PartnerStatusEnum::tryFrom($status) === null) {
            return;
        }

        $query->whereIn('type', self::NOTIFICATIONS)->where('data->partner_status', $status);
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

    /** Le statut qu'annonce une notification de partenaire ; `null` pour une autre notification. */
    private function status(DatabaseNotification $notification): ?PartnerStatusEnum
    {
        if (! in_array($notification->getAttribute('type'), self::NOTIFICATIONS, true)) {
            return null;
        }

        return PartnerStatusEnum::tryFrom((string) (((array) $notification->data)['partner_status'] ?? ''));
    }
}
