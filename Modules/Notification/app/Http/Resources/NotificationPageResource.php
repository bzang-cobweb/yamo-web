<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;

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
     * @return array{items: list<array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null, summary: string|null, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, read: bool, created_at: string|null}>, next_cursor: string|null, filters: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        $items = [];

        // Qui agit, chargé une fois pour toute la page.
        $ids = [];
        foreach ($this->resource->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $ids[] = (int) $notification->getAttribute('user_id');
        }
        $actors = User::query()->whereKey(array_unique($ids))->get()->keyBy(fn (User $user): int => (int) $user->getKey());

        foreach ($this->resource->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $id = $notification->getAttribute('user_id');
            $items[] = (new NotificationResource($notification))->withActor($id === null ? null : $actors->get((int) $id))->toArray($request);
        }

        return ['items' => $items, 'next_cursor' => $this->nextCursor(), /** Filtres proposés, en champs de formulaire (`?filter[nom]=valeur`), comme une liste (décision 2026-11-20-filtres-notifications). */
            'filters' => array_map(fn (Field $field): array => $field->toArray(), $this->filters)];
    }

    private function nextCursor(): ?string
    {
        return $this->resource->nextCursor()?->encode();
    }
}
