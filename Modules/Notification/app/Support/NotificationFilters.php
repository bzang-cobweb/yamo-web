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
            // Le type, parmi ceux de ses notifications (« Demande de logement acceptée »).
            Field::select('type')
                ->label(__('notification::notification.filter_type'))
                ->options($this->typeOptions($user)),
            ...array_merge(...array_map(fn (string $filter): array => app($filter)->fields($user), $this->filters)),
        ];
    }

    /**
     * Les types inscrits par les modules (classe => libellé).
     *
     * @return array<string, string>
     */
    private function types(): array
    {
        return array_merge(...array_map(fn (string $filter): array => app($filter)->types(), $this->filters));
    }

    /**
     * Les types présents dans ses notifications, avec leur libellé, dans l'ordre des modules.
     *
     * @return array<string, string>
     */
    private function typeOptions(User $user): array
    {
        $present = $user->notifications()->getQuery()->distinct()->pluck('type')->all();

        return array_filter($this->types(), fn (string $type): bool => in_array($type, $present, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Les membres qui agissent dans ses notifications, par nom.
     *
     * @return array<string, string>
     */
    private function members(User $user): array
    {
        return User::query()
            ->whereIn('id', $user->notifications()->getQuery()->whereNotNull('user_id')->select('user_id'))
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
        $type = $filters['type'] ?? null;
        if (is_string($type) && array_key_exists($type, $this->types())) {
            $query->where('type', $type);
        }

        $member = $filters['user'] ?? null;
        if (is_scalar($member) && ctype_digit((string) $member)) {
            $query->where('user_id', (int) $member);
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
