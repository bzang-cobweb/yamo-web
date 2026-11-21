<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Notification\Contracts\SentAsRead;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Support\BookingText;

/**
 * Une étape d'une demande, notifiée à l'autre partie (décision
 * 2026-11-19-notifications-reservations). Une seule classe pour toutes les étapes,
 * nommée par `$step` et enregistrée dans `data.step` : le filtre « Type » porte sur la
 * seule colonne `type` (décision 2026-11-20-notification-demande-unique). La notification est mise en file, envoyée après
 * l'enregistrement, toujours dans l'onglet « Notifications » de l'app (canal
 * `database`, décision 2026-11-19-contrat-centre-notifications), et sur les
 * canaux que le destinataire peut recevoir (`User::notificationChannels()`),
 * dans sa langue (`HasLocalePreference`).
 * Les textes sont dans `offer::booking.notify.<clé>` (fr, en, de).
 *
 * `$own` : la réponse du propriétaire, tracée dans son propre fil (décision
 * 2026-11-20-reponse-dans-son-fil) — dans l'onglet seulement, déjà lue, sans qui agit,
 * son texte « Vous avez accepté la demande de … ». Le suivi ne la montre pas : la
 * notification du membre porte déjà l'étape.
 */
class BookingRequested extends Notification implements HasNotificationThread, HasNotificationUser, SentAsRead, ShouldQueue
{
    use Queueable;

    /** Le statut à l'envoi : la demande, rechargée quand la file traite la notification, a pu changer depuis. */
    public readonly BookingStatusEnum $status;

    public function __construct(public readonly Booking $booking, public readonly BookingStepEnum $step, public readonly bool $own = false)
    {
        $this->status = $booking->status;

        // La connexion de file n'attend pas la fin des transactions : la notification, si.
        $this->afterCommit();
    }

    /** La clé des textes : `requested`, `accepted`, `refused`, `cancelled`. */
    private function key(): string
    {
        return $this->step->textKey();
    }

    /**
     * La liste où retrouver la demande : `mine` (le membre) ou `received` (le propriétaire, aussi pour sa
     * propre réponse). La copie de sa demande ou de son annulation, gardée par le membre : `mine`
     * (décision 2026-11-21-reservation-comme-notification).
     */
    private function section(): string
    {
        if ($this->own) {
            return in_array($this->step, [BookingStepEnum::RECEIVED, BookingStepEnum::CANCELLED, BookingStepEnum::UPDATED], true) ? 'mine' : 'received';
        }

        return $this->step->section();
    }

    /** Le texte : celui de la notification, ou celui de sa propre réponse (`<clé>.own`). */
    private function body(): string
    {
        return __("offer::booking.notify.{$this->key()}.".($this->own ? 'own' : 'body'), $this->replacements());
    }

    /** Le message à joindre : celui du membre pour une nouvelle demande, le motif d'un refus. */
    private function note(): ?string
    {
        return match ($this->step) {
            BookingStepEnum::RECEIVED, BookingStepEnum::UPDATED => $this->booking->message,
            BookingStepEnum::REFUSED => $this->booking->refusal_message,
            default => null,
        };
    }

    /**
     * Dans l'onglet, écrite avant la réponse de l'API (file `sync`) : la page qu'on retrouve
     * après une modification montre déjà sa nouvelle étape (décision
     * 2026-11-21-demande-modifiee-notification). Push, e-mail et SMS restent en file.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        // Sa propre réponse : dans l'onglet seulement.
        return $this->own ? ['database'] : ['database', ...$notifiable->notificationChannels()];
    }

    /**
     * Dans l'onglet « Notifications » : le titre de la demande (« Demande de
     * logement »), un texte, le message saisi (celui du membre, ou le motif d'un
     * refus), la demande et l'offre, l'écran à ouvrir et son bouton. Textes figés
     * dans la langue du destinataire. La pastille de statut ne vient pas d'ici :
     * l'API la lit sur la demande, au statut réel (décision 2026-11-20-statut-reel).
     *
     * @return array{title: string, step: string, own: bool, booking_id: int, booking_status: string, body: string, summary: string, total: string|null, message: string|null, link: string, link_label: string, offer_id: int, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        $list = $this->section() === 'received' ? ReceivedBookingListing::name() : BookingListing::name();

        return [
            'title' => app(DomainRegistry::class)->require()->requestLabel(),
            // L'étape notifiée : `received`, `accepted`, `refused`, `cancelled`, `updated`.
            'step' => $this->step->value,
            // La demande et son statut à l'envoi.
            'booking_id' => (int) $this->booking->getKey(),
            'booking_status' => $this->status->value,
            'body' => $this->body(),
            // La carte : qui agit (colonne `user_id`, lu à l'affichage), puis le texte sans son nom ;
            // sa propre réponse n'a pas de qui agit : la carte montre `body`.
            'summary' => $this->own ? null : BookingText::summary($this->booking, $this->key()),
            'own' => $this->own,
            // Le total figé de la demande (« 255 000 FCFA »), en gras sur la page de la notification ; `null` pour une visite.
            'total' => $this->booking->formattedTotal(app()->getLocale()),
            'message' => $this->note(),
            // Le propriétaire traite la demande dans le calendrier de l'offre, au mois de la demande
            // (décision 2026-11-19-contrat-calendrier-offre) ; le membre retrouve ses réservations.
            'link' => $this->section() === 'received'
                ? '/offers/'.$this->booking->offer_id.'/calendar?month='.$this->booking->check_in->format('Y-m')
                : '/profile/lists/'.$list,
            // Le bouton de la page de la notification : la liste où retrouver la demande.
            // Le propriétaire traite la demande ; le membre retrouve ses réservations.
            'link_label' => $this->section() === 'received' ? __('offer::booking.notify.handle') : __('offer::booking.list.mine'),
            'offer_id' => $this->booking->offer_id,
            // Le nom du membre, dans le texte, ouvre sa page (décision 2026-11-19-page-membre).
            'text_links' => $this->textLinks(),
            // Plus de lien à part : le nom de l'offre, dans le texte, mène à l'annonce.
            'links' => [],
        ];
    }

    /** Qui agit, enregistré dans la colonne `user_id` de la notification (décision 2026-11-20-notification-user-id). */
    public function notificationUserId(object $notifiable): ?int
    {
        return $this->own ? null : (int) $this->actor()->getKey();
    }

    /** Sa propre réponse est enregistrée lue : elle fait remonter la carte sans compter comme non lue. */
    public function sentAsRead(object $notifiable): bool
    {
        return $this->own;
    }

    /** Le fil de la demande : toutes ses étapes, des deux côtés (décision 2026-11-20-suivi-demande). */
    public function notificationThread(object $notifiable): ?string
    {
        return $this->booking->thread();
    }

    /**
     * Qui agit : le membre pour le propriétaire (une demande, une annulation), le
     * propriétaire pour le membre (une réponse).
     */
    private function actor(): User
    {
        return $this->section() === 'received' ? $this->booking->user : $this->booking->offer->user;
    }

    /**
     * Les passages liés du texte (décision 2026-11-19-page-membre) : le nom du membre
     * mène à sa page (une nouvelle demande, une annulation), le nom de l'offre à
     * l'annonce. Chacun seulement s'il figure dans le texte.
     *
     * @return list<array{text: string, link: string}>
     */
    private function textLinks(): array
    {
        $replace = $this->replacements();
        $body = $this->body();
        $candidates = [
            ['text' => $replace['guest'], 'link' => '/members/'.$this->booking->user_id],
            ['text' => $replace['offer'], 'link' => '/offers/'.$this->booking->offer_id],
        ];

        return array_values(array_filter($candidates, fn (array $link): bool => $link['text'] !== '' && str_contains($body, $link['text'])));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replace = $this->replacements();
        $note = $this->note();

        return (new MailMessage)
            ->subject(__("offer::booking.notify.{$this->key()}.subject", $replace))
            ->greeting(__('offer::booking.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line(__("offer::booking.notify.{$this->key()}.body", $replace))
            ->lineIf($note !== null, __('offer::booking.notify.message', ['message' => (string) $note]))
            ->line(__('offer::booking.notify.open_app', ['section' => __('offer::booking.list.'.$this->section())]))
            ->salutation(__('offer::booking.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return __("offer::booking.notify.{$this->key()}.sms", $this->replacements());
    }

    /**
     * Les remplacements des textes, dans la langue courante (celle du destinataire).
     *
     * @return array<string, string>
     */
    private function replacements(): array
    {
        return BookingText::replacements($this->booking);
    }
}
