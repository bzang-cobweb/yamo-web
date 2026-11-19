<?php

namespace Modules\Notification\Console;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Efface les notifications de l'app plus anciennes que la durée de conservation
 * (`NOTIFICATION_RETENTION_DAYS`, 90 jours par défaut ; décision
 * 2026-11-19-contrat-centre-notifications). Planifiée chaque jour.
 */
class PruneNotificationsCommand extends Command
{
    protected $signature = 'notification:prune';

    protected $description = 'Efface les notifications plus anciennes que la durée de conservation';

    public function handle(): int
    {
        $days = max(1, (int) config('notification.retention_days', 90));
        $count = DatabaseNotification::query()->where('created_at', '<', now()->subDays($days))->delete();

        $this->info(__('notification::notification.pruned', ['count' => $count, 'days' => $days]));

        return self::SUCCESS;
    }
}
