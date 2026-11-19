<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Notification\Console\PruneNotificationsCommand;
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

    /**
     * Effacement quotidien des notifications trop anciennes (décision
     * 2026-11-19-contrat-centre-notifications).
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command(PruneNotificationsCommand::class)->daily();
    }
}
