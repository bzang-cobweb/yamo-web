<?php

namespace Modules\Notification\Http\Resources;

use Bza\Form\Support\DisplayTime;
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

    /** @var array<int, User|null> Les membres déjà lus pour cette notification et son fil (décision 2026-11-20-cache-fil). */
    private array $users = [];

    /** Son fil : sur la page d'une notification seulement (décisions 2026-11-20-suivi-demande et 2026-11-20-notification-generique). */
    private bool $withThread = false;

    /** Lue ou non, pour tout son fil ; `null` : la notification seule. */
    private ?bool $read = null;

    /**
     * Ajoute son fil : le suivi chez le lecteur, de la plus ancienne étape à la plus récente
     * (décisions 2026-11-20-suivi-demande et 2026-11-24-notification-copie-auteur), et ce qu'y ajoute le module du fil par
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

    public function withActor(?User $actor): self
    {
        $this->actor = $actor;
        $this->actorLoaded = true;

        return $this;
    }

    /**
     * @return array{id: string, title: string, tag: array{label: string, tone: string}|null, body: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null, summary: string|null, message: string|null, link: string|null, links: list<array{label: string, link: string}>, text_links: list<array{text: string, link: string}>, link_label: string|null, group: bool, read: bool, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;
        /** @var User $reader */
        $reader = $request->user();
        $threads = app(NotificationThreads::class);
        $steps = $this->withThread ? $threads->steps($notification, $reader) : [];
        // Qui a fait chaque étape, et qui a reçu chacune de ses propres actions (ses coches) : lus en une fois.
        $this->loadUsers(array_merge(
            array_map(fn (DatabaseNotification $step): int => (int) $step->getAttribute('user_id'), $steps),
            array_map(fn (DatabaseNotification $step): int => (int) $step->getAttribute('notifiable_id'), $steps),
        ));
        // Tout vient de la notification servie, l'étape de sa carte (décision 2026-11-24-entete-etape-ouverte), rédigée pour
        // le lecteur (décision 2026-11-24-notifications-fil-de-groupe) ; le suivi montre les autres.
        $data = $this->dataFor($notification, $reader);

        $item = [
            'id' => (string) $notification->getKey(),
            /** Le titre (« Demande de séjour acceptée », « Partenaire modifié », « Message »), décision 2026-11-26-titre-destinataire. */
            'title' => $this->title($notification, $reader, $data),
            /** Statut en pastille, selon le type de la notification (« Acceptée », ton `success`), à défaut celui des données ; ou `null`. */
            'tag' => $this->tag($data),
            'body' => (string) ($data['body'] ?? ''),
            /** Qui agit, lu à l'affichage (photo, nom, en ligne), ou `null`. */
            'actor' => $this->actor($notification, $reader),
            /** Le texte sans le nom de qui agit, pour la carte ; `null` : `body`. */
            'summary' => $this->summary($data),
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
            /** Du fil d'un groupe, lu par tous ses membres (un partenaire) ; faux : d'un destinataire seul (décision 2026-11-27-notification-group). */
            'group' => $threads->isGroup($notification),
            /** Lue : pour la carte d'une demande, toutes ses étapes le sont. */
            'read' => $this->read ?? $this->isRead($notification, $reader),
            /** Date ISO 8601. */
            'created_at' => $this->date($notification),
            /** Formatées par le serveur (décision 2026-11-25-dates-formatees-serveur) : relative pour la carte (« il y a 5 min »,
                « hier à 10:30 »), complète pour le détail (« mercredi 18 novembre 2026 10:30 »). */
            'date' => DisplayTime::relative($notification->created_at),
            'stamp' => DisplayTime::stamp($notification->created_at, full: true),
        ];

        if (! $this->withThread) {
            return $item;
        }

        /** Le suivi de son fil chez le lecteur, de la plus ancienne étape à la plus récente ; vide sans fil. */
        $item['history'] = $this->history($steps, $reader);
        /**
         * L'en-tête de la page, sous la pastille : un titre (en gras, en lien vers `link` s'il y en a un) et un
         * sous-titre ; `null` : le texte de la notification à la place. Donné par le module du fil (une demande : le nom
         * du logement et son titre composé).
         */
        $item['heading'] = null;
        /**
         * Des lignes sous l'en-tête, libellé et valeur (les dernières modifications d'un partenaire) ; `null` : aucune.
         * Données par le module du fil (décision 2026-11-26-partenaire-dernieres-modifications).
         */
        $item['lines'] = null;
        /** Le titre des lignes dans le panneau « Info » de l'app ; `null` : aucun (décision 2026-11-27-detail-sans-zone-fixe). */
        $item['lines_title'] = null;

        /**
         * Le menu ⋮ de la page (décision 2026-11-27-menu-notification-serveur), dans l'ordre : chaque entrée a une clé, un
         * libellé et un écran de l'app à ouvrir (`link`) ; sans `link`, l'app ouvre le panneau que nomme la clé (`info` : la
         * pastille, l'en-tête et les lignes). Le module du fil peut en ajouter.
         */
        $item['menu'] = [
            ['key' => 'info', 'label' => (string) __('notification::notification.menu.info'), 'link' => null],
        ];

        // Le module du fil complète la réponse (une demande : `booking`).
        $thread = $notification->getAttribute('thread');
        if (is_string($thread)) {
            $event = new NotificationThreadSerializing($notification, $thread, $item);
            event($event);
            $item = $event->data;
        }

        return $item;
    }

    /** Lue : reçue par le lecteur et marquée lue, ou pas reçue par lui (sa propre action, celle d'un groupe). */
    private function isRead(DatabaseNotification $notification, User $reader): bool
    {
        if (! app(NotificationThreads::class)->isReceivedBy($notification, $reader)) {
            return true;
        }

        return $notification->read_at !== null;
    }

    /**
     * Les données d'une notification pour le lecteur : telles quelles s'il l'a reçue ; sinon rédigées pour lui par son
     * module (sa propre action, le fil d'un de ses groupes), à défaut telles quelles.
     *
     * @return array<mixed>
     */
    private function dataFor(DatabaseNotification $notification, User $reader): array
    {
        if (app(NotificationThreads::class)->isReceivedBy($notification, $reader)) {
            return (array) $notification->data;
        }

        return app(NotificationFilters::class)->forReader($notification, $reader) ?? (array) $notification->data;
    }

    /**
     * Les étapes de son fil : leur libellé (« Demande acceptée »), qui agit, le message
     * joint, la date (décision 2026-11-20-suivi-demande). Pour une étape du lecteur, ce
     * qu'est devenue la notification envoyée à l'autre partie (`delivery`, décision
     * 2026-11-20-coches-lecture) : `sent` (il ne s'est pas connecté depuis), `delivered`
     * (connecté depuis, pas lue), `read` (lue) ; `null` pour une étape de l'autre.
     *
     * @param  list<DatabaseNotification>  $steps
     * @return list<array{id: string, label: string, actor: array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null, message: string|null, created_at: string|null, edited: bool, delivery: string|null}>
     */
    private function history(array $steps, User $reader): array
    {
        $filters = app(NotificationFilters::class);
        $threads = app(NotificationThreads::class);

        return array_map(function (DatabaseNotification $step) use ($filters, $threads, $reader): array {
            $actor = $this->user((int) $step->getAttribute('user_id'));
            // Sa propre action, reçue par un autre : ses coches se lisent sur elle (décision
            // 2026-11-24-notifications-fil-de-groupe).
            $mine = $threads->isOwn($step, $reader) && ! $threads->isReceivedBy($step, $reader);

            return [
                'id' => (string) $step->getKey(),
                'label' => $filters->step($step, $reader),
                'actor' => $actor === null ? null : ActorData::from($actor),
                'message' => $this->message((array) $step->data),
                'created_at' => $this->date($step),
                /** Le moment de l'étape, formaté (« 10:30 », « 18 nov. 2026 10:30 »). */
                'stamp' => DisplayTime::stamp($step->created_at),
                /** Une étape modifiée (`data.edited`) : « Modifié » près de sa date (décision 2026-11-21-etape-modifiee-date). */
                'edited' => (bool) (((array) $step->data)['edited'] ?? false),
                'delivery' => $mine ? $this->delivery($step, $this->user((int) $step->getAttribute('notifiable_id'))) : null,
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
     * @return array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}|null
     */
    /**
     * Lit en une requête les membres qui ne le sont pas encore.
     *
     * @param  list<int>  $ids
     */
    private function loadUsers(array $ids): void
    {
        $missing = array_values(array_diff(array_unique(array_filter($ids)), array_keys($this->users)));
        if ($missing === []) {
            return;
        }

        $found = User::query()->whereKey($missing)->get()->keyBy(fn (User $user): int => (int) $user->getKey());
        foreach ($missing as $id) {
            $this->users[$id] = $found->get($id);
        }
    }

    /** Un membre, lu une seule fois ; `null` s'il n'existe plus. */
    private function user(int $id): ?User
    {
        $this->loadUsers([$id]);

        return $this->users[$id] ?? null;
    }

    private function actor(DatabaseNotification $notification, User $reader): ?array
    {
        // Sa propre action : la carte ne montre pas son lecteur (décision 2026-11-24-notifications-fil-de-groupe).
        if (app(NotificationThreads::class)->isOwn($notification, $reader)) {
            return null;
        }

        $id = $notification->getAttribute('user_id');
        $actor = $this->actorLoaded ? $this->actor : ($id === null ? null : $this->user((int) $id));

        if ($actor === null) {
            return null;
        }

        return ActorData::from($actor);
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
     * Le titre pour le lecteur (décision 2026-11-26-titre-destinataire) :
     * - il a reçu la notification : le titre enregistré à l'envoi, déjà rédigé pour lui, dans sa langue, par la même
     *   fonction que `title()` de son module ;
     * - il ne l'a pas reçue (sa propre action, le fil d'un groupe) : le titre que calcule son module pour lui, dans sa
     *   langue ; à défaut (un type sans module), le titre enregistré.
     *
     * @param  array<mixed>  $data
     */
    private function title(DatabaseNotification $notification, User $reader, array $data): string
    {
        $stored = (string) ($data['title'] ?? '');
        if (app(NotificationThreads::class)->isReceivedBy($notification, $reader)) {
            return $stored;
        }

        return app(NotificationFilters::class)->title($notification, $reader) ?? $stored;
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
