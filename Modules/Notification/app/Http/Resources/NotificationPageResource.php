<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Support\NotificationFilters;

/**
 * Une page de notifications, chacune sous la forme de `NotificationResource`, et le
 * curseur de la page suivante.
 *
 * @property CursorPaginator<int, DatabaseNotification> $resource
 */
class NotificationPageResource extends JsonResource
{
    /** @var list<Field> */
    private array $filters = [];

    /** Le nombre de cartes de la liste, recherche et filtres compris. */
    private int $total = 0;

    /** @var list<string> Les fils de la page qui ont une notification non lue. */
    private array $unreadThreads = [];

    /**
     * La carte d'une demande est non lue tant qu'une de ses étapes l'est (décision 2026-11-20-suivi-demande).
     *
     * @param  list<string>  $threads
     */
    public function withUnreadThreads(array $threads): self
    {
        $this->unreadThreads = $threads;

        return $this;
    }

    /** Le nombre de cartes de toute la liste, pas seulement de la page (décision 2026-11-22-notifications-nombre). */
    public function withTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Les filtres de la liste, servis avec chaque page (décision 2026-11-20-filtres-notifications).
     *
     * @param  list<Field>  $filters
     */
    public function withFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array{items: list<array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null, summary: string|null, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, read: bool, created_at: string|null}>, next_cursor: string|null, count: string, filters: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        /** @var list<DatabaseNotification> $notifications */
        $notifications = array_values($this->resource->items());

        // Qui agit, chargé une fois pour toute la page.
        $ids = array_unique(array_map(fn (DatabaseNotification $notification): int => (int) $notification->getAttribute('user_id'), $notifications));
        $actors = User::query()->whereKey($ids)->get()->keyBy(fn (User $user): int => (int) $user->getKey());
        // Ce que les modules liront pour chaque carte (une demande : son statut), en une fois.
        app(NotificationFilters::class)->prepare($notifications);

        $items = [];
        foreach ($notifications as $notification) {
            $id = $notification->getAttribute('user_id');
            $thread = $notification->getAttribute('thread');
            $resource = (new NotificationResource($notification))->withActor($id === null ? null : $actors->get((int) $id));
            if (is_string($thread)) {
                $resource->withRead(! in_array($thread, $this->unreadThreads, true));
            }
            $items[] = $resource->toArray($request);
        }

        return ['items' => $items, 'next_cursor' => $this->nextCursor(),
            /** Le nombre de cartes, rédigé (« 12 notifications », « Aucune notification »), recherche et filtres compris. */
            'count' => trans_choice('notification::notification.count', $this->total, ['count' => $this->total]),
            /** Filtres proposés, en champs de formulaire (`?filter[nom]=valeur`), comme une liste (décision 2026-11-20-filtres-notifications). */
            'filters' => array_map(fn (Field $field): array => $field->asFilter()->toArray(), $this->filters)];
    }

    private function nextCursor(): ?string
    {
        return $this->resource->nextCursor()?->encode();
    }
}
