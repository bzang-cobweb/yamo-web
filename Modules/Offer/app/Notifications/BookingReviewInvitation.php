<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Support\BookingText;

/**
 * L'invitation à donner son avis, le lendemain de la fin d'un séjour ou d'une visite (décision
 * 2026-11-22-invitation-avis) : dans l'onglet, et par e-mail et SMS selon les canaux du membre ; elle ouvre le
 * formulaire d'avis du logement. Hors du fil de la demande : une carte à elle.
 */
final class BookingReviewInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', ...$notifiable->notificationChannels()] : [];
    }

    /**
     * @return array{title: string, body: string, offer_id: int, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        $body = $this->body();

        return [
            'title' => __('offer::booking.notify.review.title'),
            'body' => $body,
            'offer_id' => (int) $this->booking->offer_id,
            'link' => $this->link(),
            'link_label' => __('offer::booking.review_cta'),
            // Le nom du logement, dans le texte, mène à l'annonce.
            'text_links' => BookingText::offerLink($this->booking, $body),
            'links' => [],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('offer::booking.notify.review.subject', BookingText::replacements($this->booking)))
            ->greeting(__('offer::booking.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line($this->body())
            ->line(__('offer::booking.notify.review.open_app'))
            ->salutation(__('offer::booking.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return __('offer::booking.notify.review.sms'.$this->suffix(), BookingText::replacements($this->booking));
    }

    private function body(): string
    {
        return __('offer::booking.notify.review.body'.$this->suffix(), BookingText::replacements($this->booking));
    }

    /** `_visit` pour une visite. */
    private function suffix(): string
    {
        return $this->booking->type === BookingTypeEnum::VISIT ? '_visit' : '';
    }

    private function link(): string
    {
        return '/offers/'.$this->booking->offer_id.'/review';
    }
}
