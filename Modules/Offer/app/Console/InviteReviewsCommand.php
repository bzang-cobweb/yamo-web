<?php

namespace Modules\Offer\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingReviewInvitation;

/**
 * Chaque jour, invite à donner son avis le membre dont le séjour ou la visite s'est terminé (décision
 * 2026-11-22-invitation-avis) : une seule fois par demande, pas s'il a déjà donné son avis sur ce logement, et
 * seulement pour une demande finie depuis une semaine au plus (pas de rattrapage des anciennes).
 */
class InviteReviewsCommand extends Command
{
    protected $signature = 'booking:invite-reviews';

    protected $description = 'Invite à donner son avis après un séjour ou une visite terminé.';

    /** Jusqu'à combien de jours après la fin une demande est encore invitée. */
    public const WINDOW_DAYS = 7;

    public function handle(): int
    {
        $last = 'date(case when check_out is not null and check_out > check_in then check_out else check_in end)';
        $count = 0;

        Booking::query()
            ->completed()
            ->whereRaw("{$last} >= ?", [today()->subDays(self::WINDOW_DAYS)->toDateString()])
            ->whereNull('review_invited_at')
            ->whereHas('offer', fn (Builder $offer) => $offer->where('status', 'published'))
            ->whereDoesntHave('offer.reviews', fn (Builder $reviews) => $reviews->whereColumn('offer_reviews.user_id', 'bookings.user_id'))
            ->with(['offer', 'user'])
            ->orderBy('id')
            ->each(function (Booking $booking) use (&$count): void {
                if ($booking->user === null) {
                    return;
                }

                $booking->user->notify(new BookingReviewInvitation($booking));
                $booking->forceFill(['review_invited_at' => now()])->save();
                $count++;
            });

        $this->info(__('offer::booking.notify.review.sent', ['count' => $count]));

        return self::SUCCESS;
    }
}
