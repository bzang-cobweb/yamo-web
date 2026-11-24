<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Lists\BookingListing;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Support\BookingText;

/**
 * Une étape d'une demande, notifiée à l'autre partie (décision
 * 2026-11-19-notifications-reservations). Une classe par action (décision
 * 2026-11-22-notification-par-action) : `BookingRequestReceived`, `…Updated`, `…Accepted`,
 * `…Refused`, `…Cancelled` ; l'étape est aussi enregistrée dans `data.step`. Le filtre
 * « Type » les regroupe (`NotificationFilter::types()`). La notification est mise en file, envoyée après
 * l'enregistrement, toujours dans l'onglet « Notifications » de l'app (canal
 * `database`, décision 2026-11-19-contrat-centre-notifications), et sur les
 * canaux que le destinataire peut recevoir (`User::notificationChannels()`),
 * dans sa langue (`HasLocalePreference`).
 * Les textes sont dans `offer::booking.notify.<clé>` (fr, en, de).
 *
 * Une seule notification par action, à l'autre partie (décision 2026-11-24-notifications-fil-de-groupe) ; celle qui
 * agit en est l'auteur (`user_id`) et la retrouve dans son fil, rédigée pour elle à l'affichage (`toArray()` pour
 * elle : `isOwn()`, son texte « Vous avez accepté la demande de … »). Qui agit se lit dans l'étape : le membre pour
 * une demande, une modification ou une annulation, le propriétaire pour une réponse.
 */
abstract class BookingNotification extends Notification implements HasNotificationThread, HasNotificationUser, ShouldQueue
{
    use Queueable;

    /** Le statut à l'envoi : la demande, rechargée quand la file traite la notification, a pu changer depuis. */
    public readonly BookingStatusEnum $status;

    /** L'étape, donnée par la classe. */
    public readonly BookingStepEnum $step;

    /** L'étape notifiée par la classe. */
    abstract public static function step(): BookingStepEnum;

    /** La notification de l'étape `$step`, de sa classe. */
    public static function for(Booking $booking, BookingStepEnum $step, bool $edited = false, bool $inTab = true): self
    {
        $class = $step->notification();

        return new $class($booking, $edited, $inTab);
    }

    /**
     * Le type d'une notification, sur sa carte (« Nouvelle demande », « Demande acceptée »), dans la langue
     * courante ; la copie de sa propre demande : « Demande envoyée » (décision 2026-11-22-types-sans-logement).
     */
    public static function title(BookingStepEnum $step, bool $own): string
    {
        return __('offer::booking.notify.types.'.$step->value.($own && $step === BookingStepEnum::RECEIVED ? '_own' : ''));
    }

    /**
     * `$edited` : une demande modifiée dont le message diffère du précédent (décision 2026-11-21-etape-modifiee-date) ;
     * `$inTab` faux : l'onglet a déjà l'étape (réécrite sur place), seulement l'e-mail et le SMS.
     */
    final public function __construct(public readonly Booking $booking, public readonly bool $edited = false, public readonly bool $inTab = true)
    {
        $this->step = static::step();
        $this->status = $booking->status;

        // La connexion de file n'attend pas la fin des transactions : la notification, si.
        $this->afterCommit();
    }

    /** La clé des textes : `requested`, `accepted`, `refused`, `cancelled`. */
    private function key(): string
    {
        return $this->step->textKey();
    }

    /** Le lecteur est-il celui qui agit ? Sa propre action, rédigée pour lui (décision 2026-11-24-notifications-fil-de-groupe). */
    public function isOwn(object $notifiable): bool
    {
        return $notifiable instanceof User && (string) $notifiable->getKey() === (string) $this->actor()->getKey();
    }

    /**
     * La liste où retrouver la demande : `mine` (le membre) ou `received` (le propriétaire, aussi pour sa
     * propre réponse). La copie de sa demande ou de son annulation, gardée par le membre : `mine`
     * (décision 2026-11-21-reservation-comme-notification).
     */
    private function section(object $notifiable): string
    {
        if ($this->isOwn($notifiable)) {
            return $this->step->section() === 'received' ? 'mine' : 'received';
        }

        return $this->step->section();
    }

    /** Le texte : celui de la notification, ou celui de sa propre action (`<clé>.own`). */
    private function body(object $notifiable): string
    {
        return __("offer::booking.notify.{$this->key()}.".($this->isOwn($notifiable) ? 'own' : 'body'), $this->replacements());
    }

    /** Le message à joindre : celui du membre pour une demande, le motif d'un refus ou d'une annulation. */
    private function note(): ?string
    {
        return match ($this->step) {
            BookingStepEnum::RECEIVED, BookingStepEnum::UPDATED => $this->booking->message,
            BookingStepEnum::REFUSED => $this->booking->refusal_message,
            // Le message d'une acceptation (décision 2026-11-22-accepter-avec-message).
            BookingStepEnum::ACCEPTED => $this->booking->acceptance_message,
            // Le message joint à une annulation (décision 2026-11-21-annuler-depuis-le-detail).
            BookingStepEnum::CANCELLED => $this->booking->cancel_message,
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

        $channels = $notifiable->notificationChannels();

        return $this->inTab ? ['database', ...$channels] : $channels;
    }

    /**
     * Dans l'onglet « Notifications » : le titre de la demande (« Demande de
     * logement »), un texte, le message saisi (celui du membre, ou le motif d'un
     * refus), la demande et l'offre, l'écran à ouvrir et son bouton. Textes figés
     * dans la langue du destinataire. La pastille de statut ne vient pas d'ici :
     * l'API la lit sur la demande, au statut réel (décision 2026-11-20-statut-reel).
     *
     * @return array{title: string, step: string, booking_id: int, booking_status: string, body: string, summary: string, total: string|null, message: string|null, edited: bool, link: string, link_label: string, offer_id: int, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        $own = $this->isOwn($notifiable);
        $section = $this->section($notifiable);
        $list = $section === 'received' ? ReceivedBookingListing::name() : BookingListing::name();

        return [
            // Le type de l'action (« Nouvelle demande », « Demande acceptée »), décision 2026-11-22-notification-par-action.
            'title' => self::title($this->step, $own),
            // L'étape notifiée : `received`, `accepted`, `refused`, `cancelled`, `updated`.
            'step' => $this->step->value,
            // La demande et son statut à l'envoi.
            'booking_id' => (int) $this->booking->getKey(),
            'booking_status' => $this->status->value,
            'body' => $this->body($notifiable),
            // La carte : qui agit (colonne `user_id`, lu à l'affichage), puis le texte sans son nom ;
            // sa propre action ne montre pas qui agit : la carte montre `body`.
            'summary' => $own ? null : BookingText::summary($this->booking, $this->key()),
            // Le total figé de la demande (« 255 000 FCFA »), en gras sur la page de la notification ; `null` pour une visite.
            'total' => $this->booking->formattedTotal(app()->getLocale()),
            'message' => $this->note(),
            // Une demande modifiée dont le message a changé : « Modifié » près de sa date dans le suivi
            // (décision 2026-11-21-etape-modifiee-date).
            'edited' => $this->edited,
            // Le propriétaire traite la demande dans le calendrier de l'offre, au mois de la demande
            // (décision 2026-11-19-contrat-calendrier-offre) ; le membre retrouve ses réservations.
            'link' => $section === 'received'
                ? '/offers/'.$this->booking->offer_id.'/calendar?month='.$this->booking->check_in->format('Y-m')
                : '/profile/lists/'.$list,
            // Le bouton de la page de la notification : la liste où retrouver la demande.
            // Le propriétaire traite la demande ; le membre retrouve ses réservations.
            'link_label' => $section === 'received' ? __('offer::booking.notify.handle') : __('offer::booking.list.mine'),
            'offer_id' => $this->booking->offer_id,
            // Le nom du membre, dans le texte, ouvre sa page (décision 2026-11-19-page-membre).
            'text_links' => $this->textLinks($notifiable),
            // Plus de lien à part : le nom de l'offre, dans le texte, mène à l'annonce.
            'links' => [],
        ];
    }

    /**
     * Qui agit, enregistré dans la colonne `user_id` de la notification (décision 2026-11-20-notification-user-id) : il
     * retrouve ainsi son action dans son fil (décision 2026-11-24-notifications-fil-de-groupe).
     */
    public function notificationUserId(object $notifiable): ?int
    {
        return (int) $this->actor()->getKey();
    }

    /**
     * Le fil de la demande, le même chez les deux parties (décision 2026-11-20-suivi-demande) : chacune y lit ce qu'elle a
     * reçu et ce qu'elle a fait (décision 2026-11-24-notifications-fil-de-groupe).
     */
    public function notificationThread(object $notifiable): ?string
    {
        return $this->booking->thread();
    }

    /**
     * Qui agit, selon l'étape : le membre pour une demande, une modification ou une annulation, le propriétaire pour une
     * réponse.
     */
    private function actor(): User
    {
        return $this->step->section() === 'received' ? $this->booking->user : $this->booking->offer->user;
    }

    /**
     * Les passages liés du texte (décision 2026-11-19-page-membre) : le nom du membre
     * mène à sa page (une nouvelle demande, une annulation), le nom de l'offre à
     * l'annonce. Chacun seulement s'il figure dans le texte.
     *
     * @return list<array{text: string, link: string}>
     */
    private function textLinks(object $notifiable): array
    {
        $replace = $this->replacements();
        $body = $this->body($notifiable);
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
            ->line(__('offer::booking.notify.open_app', ['section' => __('offer::booking.list.'.$this->section($notifiable))]))
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
