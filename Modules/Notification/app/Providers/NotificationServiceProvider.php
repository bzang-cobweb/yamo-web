<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Modules\Notification\Channels\DatabaseChannel;
use Modules\Notification\Console\PruneNotificationsCommand;
use Modules\Notification\Support\NotificationFilters;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificationServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Notification';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'notification';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        PruneNotificationsCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Le canal `database` remplit aussi `user_id` (décision 2026-11-20-notification-user-id).
        $this->app->bind(BaseDatabaseChannel::class, DatabaseChannel::class);
        // Les filtres de la liste, inscrits par les modules (décision 2026-11-20-filtres-notifications).
        $this->app->singleton(NotificationFilters::class);
    }

    /**
     * Effacement quotidien des notifications trop anciennes (décision
     * 2026-11-19-contrat-centre-notifications).
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command(PruneNotificationsCommand::class)->daily();
    }
}
