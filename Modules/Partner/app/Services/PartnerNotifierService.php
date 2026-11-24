<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerNotification;

/**
 * Les notifications d'un partenaire, une classe par statut, dans son fil partagé par tous ses comptes (décisions
 * 2026-11-24-partenaire-notification-par-statut et 2026-11-24-notifications-fil-de-groupe). Une seule par action dans
 * l'onglet :
 * - un ajout ou une modification depuis l'app : au compte du back-office choisi sur la page Application
 *   (`Setting::partnerNotifyUser()`, décision 2026-11-24-partenaire-compte-prevenu) ;
 * - une action au back-office (statut, ajout, modification) : à l'auteur de la dernière demande depuis l'app.
 * Ses administrateurs (`is_admin`) en reçoivent aussi l'e-mail et le SMS, sans notification dans l'onglet : ils la
 * lisent dans son fil (décision 2026-11-24-partenaire-admins-email-sms). Celui qui agit ne reçoit rien.
 */
class PartnerNotifierService
{
    /**
     * Son statut a changé au back-office : l'auteur de la demande est prévenu, ses administrateurs par e-mail et SMS.
     * Pas pour le passage en validation : il vient d'une action depuis l'app, que `submitted()` annonce déjà.
     */
    public function statusChanged(Partner $partner, int|string|null $actorId): void
    {
        if ($partner->status === PartnerStatusEnum::PENDING) {
            return;
        }

        $this->send($partner, $this->author($partner), $actorId, fn (bool $inTab): PartnerNotification => PartnerNotification::for($partner, $partner->status, inTab: $inTab));
    }

    /**
     * Il a été ajouté (`$created`) ou modifié au back-office par `$actorId`, sans changement de statut : l'auteur de la
     * demande est prévenu (« … modifié par l'équipe »), ses administrateurs par e-mail et SMS.
     */
    public function edited(Partner $partner, int|string|null $actorId, bool $created): void
    {
        $action = $created ? PartnerNotification::CREATED : PartnerNotification::UPDATED;
        $this->send($partner, $this->author($partner), $actorId, fn (bool $inTab): PartnerNotification => PartnerNotification::for($partner, $partner->status, $action, inTab: $inTab));
    }

    /**
     * Il a été ajouté ou modifié depuis l'app par `$actorId` et attend la validation : le compte du back-office choisi
     * sur la page Application est prévenu (sans compte choisi, personne), ses administrateurs par e-mail et SMS.
     */
    public function submitted(Partner $partner, int|string $actorId, bool $created): void
    {
        $actor = User::query()->find($actorId);
        $actorName = $actor === null ? '' : (string) $actor->full_name;
        $action = $created ? PartnerNotification::CREATED : PartnerNotification::UPDATED;

        // La notification de son statut : en validation pour un ajout, l'actuel pour une modification (décision
        // 2026-11-24-partenaire-modification-sans-validation).
        $this->send($partner, Setting::partnerNotifyUser(), $actorId, fn (bool $inTab): PartnerNotification => PartnerNotification::for($partner, $partner->status, $action, $actorId, $actorName, $inTab));
    }

    /**
     * `$recipient` reçoit la notification, dans l'onglet et par ses canaux ; chaque administrateur du partenaire, par
     * e-mail et SMS seulement. Ni l'un ni l'autre s'il est celui qui agit ; un administrateur déjà destinataire ne la
     * reçoit pas deux fois.
     *
     * @param  callable(bool): PartnerNotification  $notification  la notification, dans l'onglet ou non
     */
    private function send(Partner $partner, ?User $recipient, int|string|null $actorId, callable $notification): void
    {
        $skip = array_values(array_filter([$actorId === null ? null : (string) $actorId, $recipient === null ? null : (string) $recipient->getKey()]));
        $this->notifyRecipient($recipient, $actorId, $notification(true));

        $admins = $partner->users()->wherePivot('is_admin', true)->get()
            ->reject(fn (User $admin): bool => in_array((string) $admin->getKey(), $skip, true));
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification(false));
        }
    }

    /** `$recipient` reçoit `$notification`, sauf s'il n'y en a pas ou s'il est celui qui agit. */
    private function notifyRecipient(?User $recipient, int|string|null $actorId, PartnerNotification $notification): void
    {
        if ($recipient === null) {
            return;
        }
        if ((string) $recipient->getKey() === (string) $actorId) {
            return;
        }

        $recipient->notify($notification);
    }

    /**
     * L'auteur de la dernière action depuis l'app, ajout ou modification (`user_id` de la dernière notification de son
     * fil qui en a un : les actions de l'équipe n'en ont pas) ; `null` sans action depuis l'app.
     */
    private function author(Partner $partner): ?User
    {
        $authorId = DatabaseNotification::query()
            ->where('thread', $partner->groupThread())
            ->whereNotNull('user_id')
            ->latest()
            ->value('user_id');

        return $authorId === null ? null : User::query()->find($authorId);
    }
}
