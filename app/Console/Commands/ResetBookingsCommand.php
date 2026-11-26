<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingReviewInvitation;

use function Laravel\Prompts\confirm;

/**
 * Remet à zéro les demandes de démonstration (décision 2026-11-20-reset-booking) : vide
 * toutes les demandes et leurs notifications (étapes, invitations à donner un avis), puis relance `DemoSeeder`, qui
 * recrée les demandes des visiteurs au membre, à chaque étape, avec leurs notifications. Les autres notifications
 * (partenaires, messages à l'éditeur) et le reste (comptes, logements, avis) ne sont pas touchés (décision
 * 2026-11-26-reset-booking-notifications-des-demandes). Jamais en production.
 *
 *     ddev artisan booking:reset          # demande confirmation
 *     ddev artisan booking:reset --force  # sans confirmation
 */
class ResetBookingsCommand extends Command
{
    protected $signature = 'booking:reset {--force : Sans demander de confirmation}';

    protected $description = 'Vide les demandes et leurs notifications, puis remet les demandes de démonstration';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusé en production.');

            return self::FAILURE;
        }

        $bookings = Booking::query()->count();
        $notifications = $this->bookingNotifications()->count();

        if (! $this->option('force') && ! confirm("Supprimer {$bookings} demandes et leurs {$notifications} notifications, puis remettre les demandes de démonstration ?", default: false)) {
            $this->info('Rien n\'a été modifié.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $this->bookingNotifications()->delete();
            Booking::query()->delete();
        });

        $this->callSilently('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info(sprintf(
            'Supprimées : %d demandes et leurs %d notifications. Recréées : %d demandes et leurs %d notifications.',
            $bookings,
            $notifications,
            Booking::query()->count(),
            $this->bookingNotifications()->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * Les notifications des demandes : leurs étapes et les invitations à donner un avis.
     *
     * @return Builder<DatabaseNotification>
     */
    private function bookingNotifications(): Builder
    {
        return DatabaseNotification::query()->whereIn('type', [...BookingStepEnum::notifications(), BookingReviewInvitation::class]);
    }
}
