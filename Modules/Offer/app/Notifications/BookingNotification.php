<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Support\BookingText;

/**
 * Une étape d'une demande, notifiée à l'autre partie (décision
 * 2026-11-19-notifications-reservations) : mise en file, envoyée après
 * l'enregistrement, toujours dans l'onglet « Notifications » de l'app (canal
 * `database`, décision 2026-11-19-contrat-centre-notifications), et sur les
 * canaux que le destinataire peut recevoir (`User::notificationChannels()`),
 * dans sa langue (`HasLocalePreference`).
 * Les textes sont dans `offer::booking.notify.<clé>` (fr, en, de).
 */
abstract class BookingNotification extends Notification implements HasNotificationUser, ShouldQueue
{
    use Queueable;

    /** Le statut à l'envoi : la demande, rechargée quand la file traite la notification, a pu changer depuis. */
    public readonly BookingStatusEnum $status;

    public function __construct(public readonly Booking $booking)
    {
        $this->status = $booking->status;

        // La connexion de file n'attend pas la fin des transactions : la notification, si.
        $this->afterCommit();
    }

    /** La clé des textes : `requested`, `accepted`, `refused`, `cancelled`. */
    abstract protected function key(): string;

    /** La liste où retrouver la demande : `mine` (le membre) ou `received` (le propriétaire). */
    abstract protected function section(): string;

    /** Un message à joindre (celui du membre, ou le motif d'un refus) ; aucun par défaut. */
    protected function note(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', ...$notifiable->notificationChannels()] : [];
    }

    /**
     * Dans l'onglet « Notifications » : le titre de la demande (« Demande de
     * logement »), un texte, le message saisi (celui du membre, ou le motif d'un
     * refus), la demande et l'offre, l'écran à ouvrir et son bouton. Textes figés
     * dans la langue du destinataire. La pastille de statut ne vient pas d'ici :
     * l'API la déduit du type de la notification (décision 2026-11-20-statut-par-type).
     *
     * @return array{title: string, booking_id: int, booking_status: string, body: string, summary: string, total: string|null, message: string|null, link: string, link_label: string, offer_id: int, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        $list = $this->section() === 'received' ? ReceivedBookingListing::name() : BookingListing::name();

        return [
            'title' => app(DomainRegistry::class)->require()->requestLabel(),
            // La demande et son statut à l'envoi.
            'booking_id' => (int) $this->booking->getKey(),
            'booking_status' => $this->status->value,
            'body' => __("offer::booking.notify.{$this->key()}.body", $this->replacements()),
            // La carte : qui agit (colonne `user_id`, lu à l'affichage), puis le texte sans son nom.
            'summary' => BookingText::summary($this->booking, $this->key()),
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
        return (int) $this->actor()->getKey();
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
        $body = __("offer::booking.notify.{$this->key()}.body", $replace);
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
    protected function replacements(): array
    {
        return BookingText::replacements($this->booking);
    }
}
