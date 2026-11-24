<?php

namespace Modules\Notification\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\NotifiableGroup;

/**
 * Les fils de notifications (décision 2026-11-20-suivi-demande) : les notifications d'un même fil (colonne `thread`,
 * `booking:12`) ne font qu'une carte, la plus récente ; elles comptent pour une non lue ; ouvrir la carte les marque
 * toutes lues ; sa page montre tout le fil. Une notification sans fil reste seule.
 *
 * Ce qu'un compte voit (décision 2026-11-24-notifications-fil-de-groupe) :
 * - les notifications qu'il a reçues ;
 * - celles de ses propres actions, reçues par un autre (`user_id`) : une action ne crée qu'une notification ;
 * - toutes celles du fil d'un groupe dont il est membre (`NotifiableGroup`, `partner:group:5`).
 * Seules celles qu'il a reçues comptent comme non lues.
 */
class NotificationThreads
{
    /** @var list<class-string<NotifiableGroup>> */
    private array $groups = [];

    /** @var array<string, list<string>> Les fils de groupe déjà lus pendant la requête HTTP en cours, par compte. */
    private array $groupThreads = [];

    private ?Request $request = null;

    /**
     * Inscrit un modèle de groupe, dont le fil se lit par tous ses membres.
     *
     * @param  class-string<NotifiableGroup>  $group
     */
    public function registerGroup(string $group): void
    {
        if (! in_array($group, $this->groups, true)) {
            $this->groups[] = $group;
            $this->groupThreads = [];
        }
    }

    /**
     * Les fils des groupes dont le compte est membre, lus une seule fois par requête HTTP : la liste les demande pour
     * sa requête et pour chaque sous-requête de `latestOnly()`. Une nouvelle requête repart de zéro : le compte a pu
     * être rattaché ou détaché depuis.
     *
     * @return list<string>
     */
    public function groupThreadsOf(User $user): array
    {
        $this->fresh();
        $key = (string) $user->getKey();
        if (! array_key_exists($key, $this->groupThreads)) {
            $this->groupThreads[$key] = array_values(array_merge(...array_map(fn (string $group): array => $group::groupThreadsOf($user), $this->groups)));
        }

        return $this->groupThreads[$key];
    }

    /** Une autre requête HTTP (un test en enchaîne plusieurs) : on oublie les fils lus. */
    private function fresh(): void
    {
        $request = app()->bound('request') ? app('request') : null;
        if ($request !== $this->request) {
            $this->request = $request;
            $this->groupThreads = [];
        }
    }

    /**
     * Les notifications que voit le compte : reçues, de ses actions, ou du fil d'un de ses groupes.
     *
     * @return Builder<DatabaseNotification>
     */
    public function visibleTo(User $user): Builder
    {
        $query = DatabaseNotification::query();
        $this->whereVisible($query->getQuery(), $user, 'notifications');

        return $query;
    }

    /**
     * Restreint `$query` (la table `$table`, ou son alias) à ce que voit le compte.
     */
    private function whereVisible(QueryBuilder $query, User $user, string $table): void
    {
        $groups = $this->groupThreadsOf($user);

        $query->where(function (QueryBuilder $visible) use ($user, $table, $groups): void {
            $visible->where(fn (QueryBuilder $received) => $received
                ->where("{$table}.notifiable_type", $user->getMorphClass())
                ->where("{$table}.notifiable_id", $user->getKey()))
                ->orWhere("{$table}.user_id", $user->getKey());
            if ($groups !== []) {
                $visible->orWhereIn("{$table}.thread", $groups);
            }
        });
    }

    /**
     * Ne garde, pour chaque fil, que sa notification la plus récente parmi celles que voit le compte.
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public function latestOnly(Builder $query, User $user): void
    {
        $query->where(fn (Builder $latest) => $latest->whereNull('notifications.thread')
            ->orWhereNotExists(function (QueryBuilder $later) use ($user): void {
                $later->select(DB::raw(1))
                    ->from('notifications as later')
                    ->whereColumn('later.thread', 'notifications.thread')
                    ->where(fn (QueryBuilder $newer) => $newer->whereColumn('later.created_at', '>', 'notifications.created_at')
                        ->orWhere(fn (QueryBuilder $same) => $same->whereColumn('later.created_at', 'notifications.created_at')->whereColumn('later.id', '>', 'notifications.id')));
                $this->whereVisible($later, $user, 'later');
            }));
    }

    /**
     * Le nombre de non lues, un fil comptant pour une : les fils distincts, plus
     * chaque notification sans fil. Pas de `coalesce(thread, id)` : sous MariaDB, `id`
     * est de type `uuid` et le mélange des deux types compte chaque ligne à part.
     */
    public function unreadCount(User $user): int
    {
        return (int) $user->unreadNotifications()->getQuery()->reorder()
            ->selectRaw('count(distinct thread) + sum(case when thread is null then 1 else 0 end) as unread')
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

    /**
     * Marque lues celles du fil que le compte a reçues ; sans fil, la notification, si elle lui est adressée.
     */
    public function markRead(User $user, DatabaseNotification $notification): void
    {
        $thread = $notification->getAttribute('thread');
        if (is_string($thread)) {
            $user->unreadNotifications()->getQuery()->where('thread', $thread)->update(['read_at' => now()]);

            return;
        }

        if ($this->isReceivedBy($notification, $user)) {
            $notification->markAsRead();
        }
    }

    /**
     * Le fil de la notification, tel que le voit le lecteur, de la plus ancienne étape à la plus récente ; vide sans
     * fil. Une action n'y figure qu'une fois : sa notification, qu'il l'ait reçue ou faite, ou celle de son groupe.
     *
     * @return list<DatabaseNotification>
     */
    public function steps(DatabaseNotification $notification, User $reader): array
    {
        $thread = $notification->getAttribute('thread');
        if (! is_string($thread)) {
            return [];
        }

        return array_values($this->visibleTo($reader)->where('thread', $thread)->orderBy('created_at')->orderBy('id')->get()->all());
    }

    /** Le lecteur a-t-il fait cette action ? Qui agit (`user_id`) est le lecteur. */
    public function isOwn(DatabaseNotification $notification, User $reader): bool
    {
        $actor = $notification->getAttribute('user_id');

        return $actor !== null && (string) $actor === (string) $reader->getKey();
    }

    /** La notification a-t-elle été reçue par ce compte ? */
    public function isReceivedBy(DatabaseNotification $notification, User $user): bool
    {
        if ($notification->getAttribute('notifiable_type') !== $user->getMorphClass()) {
            return false;
        }

        return (string) $notification->getAttribute('notifiable_id') === (string) $user->getKey();
    }
}
