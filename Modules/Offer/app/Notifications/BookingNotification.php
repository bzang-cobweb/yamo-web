<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
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

    public function __construct(public readonly Booking $booking)
    {
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
     * Dans l'onglet « Notifications » : un titre, un texte, l'écran à ouvrir (la
     * liste où retrouver la demande). Textes figés dans la langue du destinataire.
     *
     * @return array{title: string, body: string, link: string}
     */
    public function toArray(object $notifiable): array
    {
        $list = $this->section() === 'received' ? ReceivedBookingListing::name() : BookingListing::name();

        return [
            'title' => __("offer::booking.notify.{$this->key()}.title"),
            'body' => __("offer::booking.notify.{$this->key()}.body", $this->replacements()),
            'link' => '/profile/lists/'.$list,
        ];
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
            'offer' => app(DomainRegistry::class)->require()->itemTitle($offer) ?? (string) $offer->getTranslation('title', $locale),
            'period' => $this->booking->period($locale),
            'type' => __('offer::booking.notify.type_lower.'.$this->booking->type->value),
        ];
    }
}
