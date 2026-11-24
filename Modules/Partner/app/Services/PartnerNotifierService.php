<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerNotification;

/**
 * Les notifications d'un partenaire, une classe par statut, dans son fil partagé par tous ses comptes (décisions
 * 2026-11-24-partenaire-notification-par-statut et 2026-11-24-notifications-fil-de-groupe). Une seule par action :
 * - un ajout ou une modification depuis l'app : au compte du back-office choisi sur la page Application
 *   (`Setting::partnerNotifyUser()`, décision 2026-11-24-partenaire-compte-prevenu), pas au membre qui agit ;
 * - une action au back-office (statut, ajout, modification) : à l'auteur de la dernière demande depuis l'app, pas à
 *   celui qui l'a faite.
 * Les autres comptes du partenaire la lisent dans son fil, sans la recevoir. Le membre qui agit depuis l'app en reçoit
 * la confirmation par e-mail et SMS (décision 2026-11-24-confirmation-auteur).
 */
class PartnerNotifierService
{
    /**
     * Son statut a changé au back-office : l'auteur de la demande est prévenu. Pas pour le passage en validation : il
     * vient d'une action depuis l'app, que `submitted()` annonce déjà.
     */
    public function statusChanged(Partner $partner, int|string|null $actorId): void
    {
        if ($partner->status === PartnerStatusEnum::PENDING) {
            return;
        }

        $this->notifyAuthor($partner, $actorId, PartnerNotification::for($partner, $partner->status));
    }

    /**
     * Il a été ajouté (`$created`) ou modifié au back-office par `$actorId`, sans changement de statut : l'auteur de la
     * demande est prévenu (« … modifié par l'équipe »).
     */
    public function edited(Partner $partner, int|string|null $actorId, bool $created): void
    {
        $action = $created ? PartnerNotification::CREATED : PartnerNotification::UPDATED;
        $this->notifyAuthor($partner, $actorId, PartnerNotification::for($partner, $partner->status, $action));
    }

    /**
     * Il a été ajouté ou modifié depuis l'app par `$actorId` et attend la validation : celui qui agit en reçoit la
     * confirmation par e-mail et SMS ; le compte du back-office choisi sur la page Application est prévenu, sauf s'il
     * est celui qui agit ; sans compte choisi, personne.
     */
    public function submitted(Partner $partner, int|string $actorId, bool $created): void
    {
        $actor = User::query()->find($actorId);
        $actorName = $actor === null ? '' : (string) $actor->full_name;
        $action = $created ? PartnerNotification::CREATED : PartnerNotification::UPDATED;
        $actor?->notify(PartnerNotification::for($partner, PartnerStatusEnum::PENDING, $action, $actorId, $actorName, inTab: false));

        $recipient = Setting::partnerNotifyUser();
        if ($recipient === null) {
            return;
        }
        if ((string) $recipient->getKey() === (string) $actorId) {
            return;
        }

        $recipient->notify(PartnerNotification::for($partner, PartnerStatusEnum::PENDING, $action, $actorId, $actorName));
    }

    /**
     * L'auteur de la dernière demande depuis l'app (`user_id` de la dernière notification « en validation » de son
     * fil) reçoit `$notification`, sauf s'il est `$actorId` ; personne sans demande depuis l'app.
     */
    private function notifyAuthor(Partner $partner, int|string|null $actorId, PartnerNotification $notification): void
    {
        $authorId = DatabaseNotification::query()
            ->where('thread', $partner->groupThread())
            ->where('type', PartnerStatusEnum::PENDING->notification())
            ->whereNotNull('user_id')
            ->latest()
            ->value('user_id');
        if ($authorId === null) {
            return;
        }
        if ((string) $authorId === (string) $actorId) {
            return;
        }

        User::query()->find($authorId)?->notify($notification);
    }
}
