<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Booking;

use function Laravel\Prompts\confirm;

/**
 * Remet à zéro les demandes de démonstration (décision 2026-11-20-reset-booking) : vide
 * toutes les demandes et toutes les notifications, puis relance `DemoSeeder`, qui recrée
 * les demandes des visiteurs au membre, à chaque étape, avec leurs notifications. Le reste
 * (comptes, logements, avis) n'est pas touché. Jamais en production.
 *
 *     ddev artisan booking:reset          # demande confirmation
 *     ddev artisan booking:reset --force  # sans confirmation
 */
class ResetBookingsCommand extends Command
{
    protected $signature = 'booking:reset {--force : Sans demander de confirmation}';

    protected $description = 'Vide les demandes et les notifications, puis remet les demandes de démonstration';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusé en production.');

            return self::FAILURE;
        }

        $bookings = Booking::query()->count();
        $notifications = DatabaseNotification::query()->count();

        if (! $this->option('force') && ! confirm("Supprimer {$bookings} demandes et {$notifications} notifications, puis remettre les demandes de démonstration ?", default: false)) {
            $this->info('Rien n\'a été modifié.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            DatabaseNotification::query()->delete();
            Booking::query()->delete();
        });

        $this->callSilently('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info(sprintf(
            'Supprimées : %d demandes, %d notifications. Recréées : %d demandes, %d notifications.',
            $bookings,
            $notifications,
            Booking::query()->count(),
            DatabaseNotification::query()->count(),
        ));

        return self::SUCCESS;
    }
}
