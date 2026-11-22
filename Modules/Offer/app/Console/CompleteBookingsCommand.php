<?php

namespace Modules\Offer\Console;

use Illuminate\Console\Command;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;

/**
 * Chaque nuit, passe au statut « Terminée » les demandes acceptées dont le dernier jour est passé : le départ d'un
 * séjour, la dernière date proposée d'une visite (décision 2026-11-22-statut-termine-enregistre). Personne n'est
 * prévenu ; l'invitation à donner son avis part le matin (`booking:invite-reviews`).
 */
class CompleteBookingsCommand extends Command
{
    protected $signature = 'booking:complete';

    protected $description = 'Passe au statut « Terminée » les demandes acceptées dont le dernier jour est passé.';

    public function handle(): int
    {
        $count = Booking::query()
            ->where('status', BookingStatusEnum::ACCEPTED)
            ->completed()
            ->update(['status' => BookingStatusEnum::COMPLETED, 'updated_at' => now()]);

        $this->info(__('offer::booking.completed_count', ['count' => $count]));

        return self::SUCCESS;
    }
}
