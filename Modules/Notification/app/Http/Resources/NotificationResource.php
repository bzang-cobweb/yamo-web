<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Notification\Support\ActorData;
use Modules\Notification\Support\NotificationFilters;
use Modules\Notification\Support\NotificationThreads;

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
    /** Qui agit, déjà chargé (une page de notifications charge tous les siens d'un coup). */
    private ?User $actor = null;

    private bool $actorLoaded = false;

    /** Les coordonnées de qui agit (e-mail, téléphone) : sur la page d'une notification seulement. */
    private bool $withContact = false;

    /** Son fil : sur la page d'une notification seulement (décisions 2026-11-20-suivi-demande et 2026-11-20-notification-generique). */
    private bool $withThread = false;

    /** Lue ou non, pour tout son fil ; `null` : la notification seule. */
    private ?bool $read = null;

    /**
     * Ajoute son fil : le suivi, de la plus ancienne étape à la plus récente, des deux
     * côtés (décision 2026-11-20-suivi-demande), et ce qu'y ajoute le module du fil par
     * l'événement `NotificationThreadSerializing` (une demande : `booking`, décision
     * 2026-11-20-notification-generique).
     */
    public function withThread(): self
    {
        $this->withThread = true;

        return $this;
    }

    /** Lue si tout son fil l'est (la carte d'une demande, décision 2026-11-20-suivi-demande). */
    public function withRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    /**
     * Ajoute l'e-mail et le téléphone de qui agit (décision 2026-11-20-coordonnees-notification).
     */
    public function withContact(): self
    {
        $this->withContact = true;

        return $this;
    }

    public function withActor(?User $actor): self
    {
        $this->actor = $actor;
        $this->actorLoaded = true;

        return $this;
    }

    /**
     * @return array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string, email: string|null, phone: string|null}|null, summary: string|null, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, read: bool, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;
        $steps = $this->withThread ? app(NotificationThreads::class)->steps($notification) : [];
        // Sur sa page, un fil se présente par sa première étape : son titre, son texte, qui
        // l'a faite (décision 2026-11-20-entete-premiere-etape).
        $head = $steps[0] ?? $notification;
        $data = (array) $head->data;
        $own = (array) $notification->data;

        $item = [
            'id' => (string) $notification->getKey(),
            'title' => (string) ($data['title'] ?? ''),
            /** Statut en pastille, selon le type de la notification (« Acceptée », ton `success`), à défaut celui des données ; ou `null`. */
            'tag' => $this->tag($data),
            'body' => (string) ($data['body'] ?? ''),
            /** Qui agit, lu à l'affichage (photo, nom, en ligne), ou `null`. */
            'actor' => $this->actor($head),
            /** Le texte sans le nom de qui agit, pour la carte ; `null` : `body`. */
            'summary' => $this->summary($data),
            /** Message joint, saisi par un utilisateur (celui du membre, le motif d'un refus), ou `null`. */
            'message' => $this->message($own),
            /** Écran de l'app à ouvrir (`/profile/lists/offer.bookings`), ou `null`. */
            'link' => $this->link($own),
            /** Liens en plus, sous le texte (« Voir l'annonce » → `/offers/12`). */
            'links' => $this->links($own),
            /** Passages du texte qui ouvrent un écran (« Paul Etoa » → `/members/37`). */
            'text_links' => $this->textLinks($data),
            /** Libellé du bouton qui ouvre `link` (« Réservations reçues »), ou `null`. */
            'link_label' => $this->linkLabel($own),
            /** Lue : pour la carte d'une demande, toutes ses étapes le sont. */
            'read' => $this->read ?? $notification->read_at !== null,
            /** Date ISO 8601. */
            'created_at' => $this->date($notification),
        ];

        if (! $this->withThread) {
            return $item;
        }

        /** Le suivi de son fil, de la plus ancienne étape à la plus récente, des deux côtés ; vide sans fil. */
        $item['history'] = $this->history($steps);

        // Le module du fil complète la réponse (une demande : `booking`).
        $thread = $notification->getAttribute('thread');
        if (is_string($thread)) {
            $event = new NotificationThreadSerializing($notification, $thread, $item);
            event($event);
            $item = $event->data;
        }

        return $item;
    }

    /**
     * Les étapes de son fil : leur libellé (« Demande acceptée »), qui agit, le message
     * joint, la date (décision 2026-11-20-suivi-demande). Pour une étape du lecteur, ce
     * qu'est devenue la notification envoyée à l'autre partie (`delivery`, décision
     * 2026-11-20-coches-lecture) : `sent` (il ne s'est pas connecté depuis), `delivered`
     * (connecté depuis, pas lue), `read` (lue) ; `null` pour une étape de l'autre.
     *
     * @param  list<DatabaseNotification>  $steps
     * @return list<array{id: string, label: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string, email: string|null, phone: string|null}|null, message: string|null, created_at: string|null, delivery: string|null}>
     */
    private function history(array $steps): array
    {
        $ids = array_values(array_unique(array_filter(array_map(fn (DatabaseNotification $step): int => (int) $step->getAttribute('user_id'), $steps))));
        $actors = User::query()->whereKey($ids)->get()->keyBy(fn (User $user): int => (int) $user->getKey());
        $filters = app(NotificationFilters::class);
        $reader = (int) $this->resource->getAttribute('notifiable_id');
        // Les destinataires des étapes du lecteur : leur dernière connexion.
        $recipients = User::query()->whereKey(array_map(
            fn (DatabaseNotification $step): int => (int) $step->getAttribute('notifiable_id'),
            array_filter($steps, fn (DatabaseNotification $step): bool => (int) $step->getAttribute('user_id') === $reader),
        ))->get()->keyBy(fn (User $user): int => (int) $user->getKey());

        return array_map(function (DatabaseNotification $step) use ($actors, $filters, $reader, $recipients): array {
            $actor = $actors->get((int) $step->getAttribute('user_id'));

            return [
                'id' => (string) $step->getKey(),
                'label' => $filters->step($step),
                'actor' => $actor === null ? null : ActorData::from($actor),
                'message' => $this->message((array) $step->data),
                'created_at' => $this->date($step),
                'delivery' => (int) $step->getAttribute('user_id') === $reader
                    ? $this->delivery($step, $recipients->get((int) $step->getAttribute('notifiable_id')))
                    : null,
            ];
        }, $steps);
    }

    /** Lue ; sinon remise si le destinataire s'est connecté depuis l'envoi ; sinon envoyée. */
    private function delivery(DatabaseNotification $step, ?User $recipient): string
    {
        if ($step->read_at !== null) {
            return 'read';
        }

        $seen = $recipient?->last_seen_at;

        return $seen !== null && $step->created_at !== null && $seen->gt($step->created_at) ? 'delivered' : 'sent';
    }

    /**
     * Qui agit (colonne `user_id`), lu à l'affichage.
     *
     * @return array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string, email: string|null, phone: string|null}|null
     */
    private function actor(DatabaseNotification $head): ?array
    {
        $id = $head->getAttribute('user_id');
        $actor = $this->actorLoaded && $head === $this->resource ? $this->actor : ($id === null ? null : User::query()->find($id));

        if ($actor === null) {
            return null;
        }

        return ActorData::from($actor, $this->withContact);
    }

    /**
     * @param  array<mixed>  $data
     */
    private function summary(array $data): ?string
    {
        return isset($data['summary']) && is_string($data['summary']) ? (string) $data['summary'] : null;
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
        // Par le module qui l'inscrit : pour une demande, son statut réel (décision 2026-11-20-statut-reel).
        $byType = app(NotificationFilters::class)->tag($this->resource);
        if ($byType !== null) {
            return ['label' => (string) $byType['label'], 'tone' => (string) $byType['tone']];
        }

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
