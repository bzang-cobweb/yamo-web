<?php

namespace Modules\Offer\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Mail\BookingMail;
use Modules\Offer\Models\Booking;

/**
 * Prévient de chaque étape d'une demande (décision 2026-11-19-contrat-reservations) :
 * une demande et une annulation, le propriétaire ; une réponse, le membre. Par
 * e-mail si l'adresse est vérifiée, par SMS si le numéro l'est, dans la langue du
 * destinataire ; après l'enregistrement seulement.
 */
class BookingNotifier
{
    public const REQUESTED = 'requested';

    public const ACCEPTED = 'accepted';

    public const REFUSED = 'refused';

    public const CANCELLED = 'cancelled';

    public function __construct(private readonly SmsSender $sms) {}

    public function notify(Booking $booking, string $event): void
    {
        $booking->loadMissing(['offer.user', 'user']);
        $toOwner = in_array($event, [self::REQUESTED, self::CANCELLED], true);
        $recipient = $toOwner ? $booking->offer->user : $booking->user;

        if (! $recipient instanceof User) {
            return;
        }

        DB::afterCommit(fn () => $this->send($recipient, $booking, $event, $toOwner));
    }

    private function send(User $recipient, Booking $booking, string $event, bool $toOwner): void
    {
        $locale = $recipient->preferredLocale();
        $domain = app(DomainRegistry::class)->require();
        $replace = [
            'app' => (string) config('app.name'),
            'guest' => (string) $booking->user->full_name,
            'offer' => $domain->itemTitle($booking->offer) ?? (string) $booking->offer->getTranslation('title', $locale),
            'period' => $booking->period($locale),
            'type' => __('offer::booking.notify.type_lower.'.$booking->type->value, [], $locale),
        ];
        $section = __('offer::booking.list.'.($toOwner ? 'received' : 'mine'), [], $locale);
        // Le message du membre au propriétaire, ou le message de refus au membre.
        $note = match ($event) {
            self::REQUESTED => $booking->message,
            self::REFUSED => $booking->refusal_message,
            default => null,
        };

        if ($recipient->email !== null && $recipient->email_verified_at !== null) {
            Mail::to($recipient)->locale($locale)->send(new BookingMail(
                __("offer::booking.notify.{$event}.subject", $replace, $locale),
                __("offer::booking.notify.{$event}.body", $replace, $locale),
                $note,
                $section,
            ));
        }

        if ($recipient->phone !== null && $recipient->phone_verified_at !== null) {
            $this->sms->send($recipient->phone, __("offer::booking.notify.{$event}.sms", $replace, $locale));
        }
    }
}
