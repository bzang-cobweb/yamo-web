<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerStatusNotification;
use Modules\Partner\Notifications\PartnerSubmittedNotification;

/**
 * Les notifications d'un partenaire (décision 2026-11-24-partenaires-notifications).
 */
class PartnerNotifierService
{
    /**
     * Son statut a changé : tous les comptes qui lui sont rattachés, administrateurs ou non, sauf celui qui l'a
     * changé, sont prévenus. Pas pour le passage en validation : il vient d'un ajout ou d'une modification depuis
     * l'app, que `submitted()` annonce déjà (décision 2026-11-24-partenaires-validation-notifications).
     */
    public function statusChanged(Partner $partner, int|string|null $actorId): void
    {
        if ($partner->status === PartnerStatusEnum::PENDING) {
            return;
        }

        $recipients = $partner->users()->when($actorId !== null, fn ($users) => $users->whereKeyNot($actorId))->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new PartnerStatusNotification($partner));
        }
    }

    /**
     * Il a été ajouté ou modifié depuis l'app et attend la validation : tous ses comptes, ceux qui peuvent le modifier,
     * l'auteur compris, sont prévenus, chacun son texte (décision 2026-11-24-partenaires-validation-notifications).
     */
    public function submitted(Partner $partner, int|string $actorId, bool $created): void
    {
        $recipients = $partner->users()->get();
        if ($recipients->isEmpty()) {
            return;
        }

        $actor = User::query()->find($actorId);
        $actorName = $actor === null ? '' : (string) $actor->full_name;
        Notification::send($recipients, new PartnerSubmittedNotification($partner, $actorId, $actorName, $created));
    }
}
