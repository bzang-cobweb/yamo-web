<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerStatusNotification;

/**
 * Les notifications d'un partenaire (décision 2026-11-24-partenaires-notifications).
 */
class PartnerNotifierService
{
    /**
     * Son statut a changé : tous les comptes qui lui sont rattachés, administrateurs ou non, sauf celui qui l'a
     * changé, sont prévenus.
     */
    public function statusChanged(Partner $partner, int|string|null $actorId): void
    {
        $recipients = $partner->users()->when($actorId !== null, fn ($users) => $users->whereKeyNot($actorId))->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new PartnerStatusNotification($partner));
        }
    }
}
