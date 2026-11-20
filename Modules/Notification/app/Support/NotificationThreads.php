<?php

namespace Modules\Notification\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;

/**
 * Les fils de notifications (décision 2026-11-20-suivi-demande) : les notifications
 * d'un même fil (colonne `thread`, `booking:12`) ne font qu'une carte, la plus
 * récente ; elles comptent pour une non lue ; ouvrir la carte les marque toutes
 * lues ; sa page montre tout le fil, des deux côtés. Une notification sans fil
 * reste seule.
 */
class NotificationThreads
{
    /**
     * Ne garde, pour chaque fil, que sa notification la plus récente (chez ce destinataire).
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public function latestOnly(Builder $query): void
    {
        $query->where(fn (Builder $latest) => $latest->whereNull('notifications.thread')
            ->orWhereNotExists(fn (QueryBuilder $later) => $later->select(DB::raw(1))
                ->from('notifications as later')
                ->whereColumn('later.notifiable_type', 'notifications.notifiable_type')
                ->whereColumn('later.notifiable_id', 'notifications.notifiable_id')
                ->whereColumn('later.thread', 'notifications.thread')
                ->where(fn (QueryBuilder $newer) => $newer->whereColumn('later.created_at', '>', 'notifications.created_at')
                    ->orWhere(fn (QueryBuilder $same) => $same->whereColumn('later.created_at', 'notifications.created_at')->whereColumn('later.id', '>', 'notifications.id')))));
    }

    /** Le nombre de non lues, un fil comptant pour une. */
    public function unreadCount(User $user): int
    {
        return (int) $user->unreadNotifications()->getQuery()->reorder()
            ->selectRaw('count(distinct coalesce(thread, id)) as unread')
            ->value('unread');
    }

    /**
     * Parmi ces fils, ceux qui ont une notification non lue chez ce destinataire.
     *
     * @param  list<string>  $threads
     * @return list<string>
     */
    public function unreadThreads(User $user, array $threads): array
    {
        if ($threads === []) {
            return [];
        }

        return $user->unreadNotifications()->getQuery()->reorder()->whereIn('thread', $threads)
            ->distinct()->pluck('thread')->map(fn (mixed $thread): string => (string) $thread)->values()->all();
    }

    /** Marque lue la notification et, si elle a un fil, toutes celles de son fil chez ce destinataire. */
    public function markRead(User $user, DatabaseNotification $notification): void
    {
        $thread = $notification->getAttribute('thread');
        if (! is_string($thread)) {
            $notification->markAsRead();

            return;
        }

        $user->unreadNotifications()->getQuery()->where('thread', $thread)->update(['read_at' => now()]);
    }

    /**
     * Le fil de la notification, des deux côtés, de la plus ancienne étape à la plus
     * récente ; vide sans fil. Sans les notifications qui tracent, chez qui agit, sa
     * propre action (`data.own`) : celle de l'autre partie porte déjà l'étape
     * (décision 2026-11-20-reponse-dans-son-fil).
     *
     * @return list<DatabaseNotification>
     */
    public function steps(DatabaseNotification $notification): array
    {
        $thread = $notification->getAttribute('thread');
        if (! is_string($thread)) {
            return [];
        }

        return array_values(DatabaseNotification::query()->where('thread', $thread)->orderBy('created_at')->orderBy('id')->get()
            ->reject(fn (DatabaseNotification $step): bool => (bool) (((array) $step->data)['own'] ?? false))->all());
    }
}
