<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;

/**
 * Une page de notifications, chacune sous la forme de `NotificationResource`, et le
 * curseur de la page suivante.
 *
 * @property CursorPaginator<int, DatabaseNotification> $resource
 */
class NotificationPageResource extends JsonResource
{
    /**
     * @return array{items: list<array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null, summary: string|null, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, read: bool, created_at: string|null}>, next_cursor: string|null}
     */
    public function toArray(Request $request): array
    {
        $items = [];

        // Qui agit, chargé une fois pour toute la page.
        $ids = [];
        foreach ($this->resource->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $id = ((array) $notification->data)['actor_id'] ?? null;
            if (is_int($id)) {
                $ids[] = $id;
            }
        }
        $actors = User::query()->whereKey(array_unique($ids))->get()->keyBy(fn (User $user): int => (int) $user->getKey());

        foreach ($this->resource->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $id = ((array) $notification->data)['actor_id'] ?? null;
            $items[] = (new NotificationResource($notification))->withActor(is_int($id) ? $actors->get($id) : null)->toArray($request);
        }

        return ['items' => $items, 'next_cursor' => $this->nextCursor()];
    }

    private function nextCursor(): ?string
    {
        return $this->resource->nextCursor()?->encode();
    }
}
