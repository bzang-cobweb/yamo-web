<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Une notification : titre, statut en pastille, texte et ses passages liés, message
 * joint, écran à ouvrir et son libellé, lue ou non, date (décision
 * 2026-11-19-contrat-centre-notifications). La même forme dans la liste et sur la
 * page d'une notification.
 *
 * @property DatabaseNotification $resource
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, read: bool, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;
        $data = (array) $notification->data;

        return [
            'id' => (string) $notification->getKey(),
            'title' => (string) ($data['title'] ?? ''),
            /** Statut en pastille sous le titre (« En attente », ton `warning`), ou `null`. */
            'tag' => $this->tag($data),
            'body' => (string) ($data['body'] ?? ''),
            /** Message joint, saisi par un utilisateur (celui du membre, le motif d'un refus), ou `null`. */
            'message' => $this->message($data),
            /** Écran de l'app à ouvrir (`/profile/lists/offer.bookings`), ou `null`. */
            'link' => $this->link($data),
            /** Liens en plus, sous le texte (« Voir l'annonce » → `/offers/12`). */
            'links' => $this->links($data),
            /** Passages du texte qui ouvrent un écran (« Paul Etoa » → `/members/37`). */
            'text_links' => $this->textLinks($data),
            /** Libellé du bouton qui ouvre `link` (« Réservations reçues »), ou `null`. */
            'link_label' => $this->linkLabel($data),
            'read' => $notification->read_at !== null,
            /** Date ISO 8601. */
            'created_at' => $this->date($notification),
        ];
    }

    /**
     * @param  array<mixed>  $data
     */
    private function linkLabel(array $data): ?string
    {
        return isset($data['link_label']) && is_string($data['link_label']) ? $data['link_label'] : null;
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
     */
    private function message(array $data): ?string
    {
        $message = $data['message'] ?? null;

        return is_string($message) && trim($message) !== '' ? $message : null;
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
                $links[] = ['label' => (string) $link['label'], 'link' => (string) $link['link']];
            }
        }

        return $links;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array{text: string, link: string}>
     */
    private function textLinks(array $data): array
    {
        $links = [];

        foreach (is_array($data['text_links'] ?? null) ? $data['text_links'] : [] as $link) {
            if (is_array($link) && is_string($link['text'] ?? null) && is_string($link['link'] ?? null)) {
                $links[] = ['text' => (string) $link['text'], 'link' => (string) $link['link']];
            }
        }

        return $links;
    }

    private function date(DatabaseNotification $notification): ?string
    {
        return $notification->created_at?->toIso8601String();
    }
}
