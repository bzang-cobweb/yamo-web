<?php

namespace Modules\Partner\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Partner\Events\PartnerEdited;
use Modules\Partner\Events\PartnerStatusChanged;
use Modules\Partner\Events\PartnerSubmitted;
use Modules\Partner\Listeners\AddPartnerToNotificationThread;
use Modules\Partner\Listeners\NotifyPartnerAdminsOfEdit;
use Modules\Partner\Listeners\NotifyPartnerUsersOfStatus;
use Modules\Partner\Listeners\NotifyPartnerUsersOfSubmission;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Un changement de statut prévient les comptes du partenaire (décision 2026-11-24-partenaires-notifications).
        PartnerStatusChanged::class => [NotifyPartnerUsersOfStatus::class],
        PartnerSubmitted::class => [NotifyPartnerUsersOfSubmission::class],
        // Ajouté ou modifié au back-office : ses administrateurs (décision 2026-11-24-partenaire-demandes).
        PartnerEdited::class => [NotifyPartnerAdminsOfEdit::class],
        // L'en-tête de la page d'une notification de son fil : le nom et le slogan (décision 2026-11-26-entete-partenaire).
        NotificationThreadSerializing::class => [AddPartnerToNotificationThread::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
