<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\CursorPaginator;

/**
 * Une page de notifications : chacune avec son titre, son statut en pastille, son texte, l'écran de l'app
 * à ouvrir (ou `null`), des liens en plus, lue ou non, et sa date ; le curseur de la page suivante.
 *
 * @property CursorPaginator<int, DatabaseNotification> $resource
 */
class NotificationPageResource extends JsonResource
{
    /**
     * @return array{items: list<array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, link: string|null, links: list<array{label: string, link: string}>, read: bool, created_at: string|null}>, next_cursor: string|null}
     */
    public function toArray(Request $request): array
    {
        $items = [];

        foreach ($this->resource->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $data = (array) $notification->data;
            $items[] = [
                'id' => (string) $notification->getKey(),
                'title' => (string) ($data['title'] ?? ''),
                /** Statut en pastille sous le titre (« En attente », ton `warning`), ou `null`. */
                'tag' => $this->tag($data),
                'body' => (string) ($data['body'] ?? ''),
                /** Écran de l'app à ouvrir (`/profile/lists/offer.bookings`), ou `null`. */
                'link' => $this->link($data),
                /** Liens en plus, sous le texte (« Voir l'annonce » → `/offers/12`). */
                'links' => $this->links($data),
                'read' => $notification->read_at !== null,
                /** Date ISO 8601. */
                'created_at' => $this->date($notification),
            ];
        }

        return ['items' => $items, 'next_cursor' => $this->nextCursor()];
    }

    /**
     * @param  array<mixed>  $data
     */
    private function link(array $data): ?string
    {
        return isset($data['link']) && is_string($data['link']) ? $data['link'] : null;
    }

    /**
     * @param  array<mixed>  $data
     * @return array{label: string, tone: string}|null
     */
    private function tag(array $data): ?array
    {
        $tag = $data['tag'] ?? null;

        return is_array($tag) && is_string($tag['label'] ?? null) && is_string($tag['tone'] ?? null)
            ? ['label' => $tag['label'], 'tone' => $tag['tone']]
            : null;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array{label: string, link: string}>
     */
    private function links(array $data): array
    {
        $links = [];

        foreach (is_array($data['links'] ?? null) ? $data['links'] : [] as $link) {
            if (is_array($link) && is_string($link['label'] ?? null) && is_string($link['link'] ?? null)) {
                $links[] = ['label' => $link['label'], 'link' => $link['link']];
            }
        }

        return $links;
    }

    private function date(DatabaseNotification $notification): ?string
    {
        return $notification->created_at?->toIso8601String();
    }

    private function nextCursor(): ?string
    {
        return $this->resource->nextCursor()?->encode();
    }
}
