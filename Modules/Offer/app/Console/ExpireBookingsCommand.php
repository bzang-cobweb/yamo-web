<?php

namespace Modules\Offer\Console;

use Illuminate\Console\Command;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;

/**
 * Chaque nuit, passe au statut « Expirée » les demandes restées en attente après leur date : l'arrivée d'un séjour,
 * la dernière date proposée d'une visite (décision 2026-11-22-statut-expire-enregistre). Personne n'est prévenu.
 */
class ExpireBookingsCommand extends Command
{
    protected $signature = 'booking:expire';

    protected $description = 'Passe au statut « Expirée » les demandes restées en attente après leur date.';

    public function handle(): int
    {
        $count = Booking::query()
            ->where('status', BookingStatusEnum::PENDING)
            ->expired()
            ->update(['status' => BookingStatusEnum::EXPIRED, 'updated_at' => now()]);

        $this->info(__('offer::booking.expired_count', ['count' => $count]));

        return self::SUCCESS;
    }
}
