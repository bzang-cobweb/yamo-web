<?php

namespace Modules\Notification\Support;

use App\Support\ContainsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;

/**
 * Les filtres de la liste des notifications (décision 2026-11-20-filtres-notifications) :
 * « Membre » (qui agit) en premier, « Type » (parmi ceux qu'inscrivent les modules),
 * puis ceux des modules (le logement).
 */
class NotificationFilters
{
    /** @var list<class-string<NotificationFilter>> */
    private array $filters = [];

    /**
     * @param  class-string<NotificationFilter>  $filter
     */
    public function register(string $filter): void
    {
        if (! in_array($filter, $this->filters, true)) {
            $this->filters[] = $filter;
        }
    }

    /**
     * @return list<Field>
     */
    public function fields(User $user): array
    {
        return [
            // Qui agit, parmi les membres que nomment ses notifications.
            Field::select('user')
                ->label(__('notification::notification.filter_user'))
                ->options($this->members($user))
                ->searchable(),
            // Le type, parmi ceux de ses notifications (« Réservation », « Message », « Partenaire »), en liste : un seul
            // (décision 2026-11-26-filtre-type-select).
            Field::select('type')
                ->label(__('notification::notification.filter_type'))
                ->options($this->typeOptions($user)),
            // Le statut, ceux du type choisi, seulement quand ce type a des statuts (décisions
            // 2026-11-24-notifications-types-groupes-statut et 2026-11-24-notifications-types-reservation-partenaire).
            Field::select('status')
                ->label(__('notification::notification.filter_status'))
                ->options($statuses = array_merge(...array_map(fn (string $filter): array => app($filter)->statuses(), $this->filters)))
                ->dependsOn('type')
                ->visibleWhen('type', array_values(array_unique(array_column($statuses, 'parent')))),
            ...array_merge(...array_map(fn (string $filter): array => app($filter)->fields($user), $this->filters)),
            // En dernier, après ceux des modules, qui suivent « Type » ; non lues : Tous (par défaut), Oui, Non, en interrupteur à trois positions
            // (décision 2026-11-21-filtre-non-lues).
            Field::segmented('unread')
                ->label(__('notification::notification.filter_unread'))
                ->options(['any' => __('notification::notification.filter_any'), 'yes' => __('notification::notification.filter_yes'), 'no' => __('notification::notification.filter_no')])
                ->default('any'),
        ];
    }

    /**
     * La pastille d'une notification, donnée par le module qui l'inscrit, ou `null`.
     *
     * @return array{label: string, tone: string}|null
     */
    /**
     * Chaque module charge en une fois ce que ces notifications demanderont.
     *
     * @param  list<DatabaseNotification>  $notifications
     */
    public function prepare(array $notifications): void
    {
        foreach ($this->filters as $filter) {
            app($filter)->prepare($notifications);
        }
    }

    public function tag(DatabaseNotification $notification): ?array
    {
        foreach ($this->filters as $filter) {
            $tag = app($filter)->tag($notification);
            if ($tag !== null) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * Le libellé d'une étape d'un fil, donné par le module qui l'inscrit ; à défaut, le
     * titre de la notification.
     */
    /** Le titre de la carte pour `$reader`, donné par le module qui l'inscrit ; à défaut, `null` (son titre). */
    public function cardTitle(DatabaseNotification $notification, User $reader): ?string
    {
        foreach ($this->filters as $filter) {
            $title = app($filter)->cardTitle($notification, $reader);
            if ($title !== null) {
                return $title;
            }
        }

        return null;
    }

    /**
     * Les données de la notification pour `$reader`, qui ne l'a pas reçue, rédigées par le module qui l'inscrit ;
     * `null` : ses données telles quelles.
     *
     * @return array<string, mixed>|null
     */
    public function forReader(DatabaseNotification $notification, User $reader): ?array
    {
        foreach ($this->filters as $filter) {
            $data = app($filter)->forReader($notification, $reader);
            if ($data !== null) {
                return $data;
            }
        }

        return null;
    }

    public function step(DatabaseNotification $notification, User $reader): string
    {
        foreach ($this->filters as $filter) {
            $step = app($filter)->step($notification, $reader);
            if ($step !== null) {
                return $step;
            }
        }

        return (string) (((array) $notification->data)['title'] ?? '');
    }

    /**
     * Les types inscrits par les modules (clé => libellé et classes de notification).
     *
     * @return array<string, array{label: string, types: list<class-string>}>
     */
    private function types(): array
    {
        return array_merge(...array_map(fn (string $filter): array => app($filter)->types(), $this->filters));
    }

    /**
     * Les types présents dans ses notifications (clé => libellé), dans l'ordre des modules.
     *
     * @return array<string, string>
     */
    private function typeOptions(User $user): array
    {
        $present = app(NotificationThreads::class)->visibleTo($user)->distinct()->pluck('type')->all();
        $options = [];
        foreach ($this->types() as $key => $type) {
            if (array_intersect($type['types'], $present) !== []) {
                $options[$key] = $type['label'];
            }
        }

        return $options;
    }

    /**
     * Les membres qui agissent dans ses notifications, par nom.
     *
     * @return array<string, string>
     */
    private function members(User $user): array
    {
        return User::query()
            // Sans lui-même : ses propres actions (décision 2026-11-24-notification-copie-auteur).
            ->whereIn('id', app(NotificationThreads::class)->visibleTo($user)->whereNotNull('user_id')->where('user_id', '!=', $user->getKey())->select('user_id'))
            ->get()
            ->sortBy(fn (User $member): string => (string) $member->full_name)
            ->mapWithKeys(fn (User $member): array => [(string) $member->getKey() => (string) $member->full_name])
            ->all();
    }

    /**
     * @param  Builder<DatabaseNotification>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $user): void
    {
        // Un type ; une liste de types est encore lue (décision 2026-11-26-filtre-type-select) ; un type inconnu est ignoré.
        $types = array_intersect_key($this->types(), array_flip(array_filter((array) ($filters['type'] ?? []), 'is_string')));
        if ($types !== []) {
            $query->whereIn('type', array_merge(...array_column($types, 'types')));
        }

        $member = $filters['user'] ?? null;
        if (is_scalar($member) && ctype_digit((string) $member)) {
            $query->where('user_id', (int) $member);
        }

        // Non lue : une carte dont le lecteur a, dans son fil, une notification reçue et pas lue (décisions
        // 2026-11-21-filtre-non-lues et 2026-11-24-filtre-non-lues-lecteur).
        $unread = $filters['unread'] ?? null;
        if (in_array($unread, ['yes', 'no'], true)) {
            $hasUnread = app(NotificationThreads::class)->unreadCard($user);
            $unread === 'yes' ? $query->where($hasUnread) : $query->whereNot($hasUnread);
        }
        foreach ($this->filters as $filter) {
            app($filter)->apply($query, $filters, $user);
        }
    }

    /**
     * Restreint `$query` aux notifications dont qui agit (prénom, nom) ou ce que les
     * modules y ajoutent (le titre du logement) contient chaque mot de `$term`.
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public function search(Builder $query, string $term): void
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim($term)) ?: [], fn (string $word): bool => $word !== ''));
        if ($words === []) {
            return;
        }

        $query->where(function (Builder $group) use ($words, $term): void {
            // Qui agit : chaque mot dans son prénom ou son nom (« Paul Etoa »).
            $group->whereIn('user_id', User::query()->select('id')->where(function (Builder $users) use ($words): void {
                foreach ($words as $word) {
                    $users->where(fn (Builder $name) => ContainsSearch::orWhere($name, ['first_name', 'last_name'], $word));
                }
            }));
            foreach ($this->filters as $filter) {
                app($filter)->orSearch($group, trim($term));
            }
        });
    }
}
