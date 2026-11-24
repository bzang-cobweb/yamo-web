<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerRequest;
use Modules\Partner\Notifications\PartnerNotification;

/**
 * Les notifications d'un partenaire, une classe par statut (décisions 2026-11-24-partenaires-notifications,
 * 2026-11-24-partenaire-notification-par-statut et 2026-11-24-partenaire-demandes) :
 * - une action depuis l'app prévient tous ses comptes, l'auteur compris ;
 * - une action au back-office (statut, ajout, modification) ne prévient que ses administrateurs (`is_admin`), jamais
 *   celui qui l'a faite.
 */
class PartnerNotifierService
{
    /**
     * Son statut a changé au back-office : ses administrateurs sont prévenus, dans le fil de la demande à laquelle
     * l'équipe répond (`$request`). Pas pour le passage en validation : il vient d'une action depuis l'app, que
     * `submitted()` annonce déjà (décision 2026-11-24-partenaires-validation-notifications).
     */
    public function statusChanged(Partner $partner, int|string|null $actorId, ?PartnerRequest $request = null): void
    {
        if ($partner->status === PartnerStatusEnum::PENDING) {
            return;
        }

        $this->notifyAdmins($partner, $actorId, PartnerNotification::for($partner, $partner->status, request: $request));
    }

    /**
     * Il a été ajouté (`$created`) ou modifié au back-office par `$actorId`, sans changement de statut : ses
     * administrateurs sont prévenus (« … modifié par l'équipe »).
     */
    public function edited(Partner $partner, int|string|null $actorId, bool $created): void
    {
        $action = $created ? PartnerRequest::CREATED : PartnerRequest::UPDATED;
        $this->notifyAdmins($partner, $actorId, PartnerNotification::for($partner, $partner->status, $action));
    }

    /**
     * Il a été ajouté ou modifié depuis l'app et attend la validation : tous ses comptes, l'auteur compris, sont
     * prévenus, chacun son texte, dans le fil de sa demande (décision 2026-11-24-partenaires-validation-notifications).
     */
    public function submitted(Partner $partner, int|string $actorId, bool $created, ?PartnerRequest $request = null): void
    {
        $recipients = $partner->users()->get();
        if ($recipients->isEmpty()) {
            return;
        }

        $actor = User::query()->find($actorId);
        $actorName = $actor === null ? '' : (string) $actor->full_name;
        $action = $created ? PartnerRequest::CREATED : PartnerRequest::UPDATED;
        Notification::send($recipients, PartnerNotification::for($partner, PartnerStatusEnum::PENDING, $action, $actorId, $actorName, $request));
    }

    /** Ses administrateurs, sauf `$actorId`, reçoivent `$notification`. */
    private function notifyAdmins(Partner $partner, int|string|null $actorId, PartnerNotification $notification): void
    {
        $recipients = $partner->admins()->when($actorId !== null, fn ($admins) => $admins->whereKeyNot($actorId))->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }
}
