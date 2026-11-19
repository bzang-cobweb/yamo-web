<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;

/**
 * Une étape d'une demande, notifiée à l'autre partie (décision
 * 2026-11-19-notifications-reservations) : mise en file, envoyée après
 * l'enregistrement, toujours dans l'onglet « Notifications » de l'app (canal
 * `database`, décision 2026-11-19-contrat-centre-notifications), et sur les
 * canaux que le destinataire peut recevoir (`User::notificationChannels()`),
 * dans sa langue (`HasLocalePreference`).
 * Les textes sont dans `offer::booking.notify.<clé>` (fr, en, de).
 */
abstract class BookingNotification extends Notification implements ShouldQueue
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
     * logement »), son statut en pastille comme dans Mes réservations, un texte, le
     * message saisi (celui du membre, ou le motif d'un refus), l'offre, l'écran à ouvrir (la liste où retrouver la demande) et un lien vers la fiche
     * de l'offre. Textes figés dans la langue du destinataire, statut figé à l'envoi.
     *
     * @return array{title: string, tag: array{label: string, tone: string}, body: string, actor_id: int, summary: string, message: string|null, link: string, link_label: string, offer_id: int, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        $list = $this->section() === 'received' ? ReceivedBookingListing::name() : BookingListing::name();

        return [
            'title' => app(DomainRegistry::class)->require()->requestLabel(),
            'tag' => ['label' => $this->status->label(), 'tone' => $this->status->tone()],
            'body' => __("offer::booking.notify.{$this->key()}.body", $this->replacements()),
            // La carte : qui agit (photo, nom, en ligne : lus à l'affichage), puis le texte sans son nom.
            'actor_id' => (int) $this->actor()->getKey(),
            'summary' => __("offer::booking.notify.{$this->key()}.summary", $this->replacements()),
            'message' => $this->note(),
            'link' => '/profile/lists/'.$list,
            // Le bouton de la page de la notification : la liste où retrouver la demande.
            'link_label' => __('offer::booking.list.'.$this->section()),
            'offer_id' => $this->booking->offer_id,
            // Le nom du membre, dans le texte, ouvre sa page (décision 2026-11-19-page-membre).
            'text_links' => $this->textLinks(),
            // Plus de lien à part : le nom de l'offre, dans le texte, mène à l'annonce.
            'links' => [],
        ];
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
        $locale = app()->getLocale();
        $offer = $this->booking->offer;

        return [
            'app' => (string) config('app.name'),
            'guest' => (string) $this->booking->user->full_name,
            // Le titre saisi par le propriétaire, pas le titre construit de la liste.
            'offer' => (string) $offer->getTranslation('title', $locale),
            'period' => $this->booking->period($locale),
            'type' => __('offer::booking.notify.type_lower.'.$this->booking->type->value),
        ];
    }
}
