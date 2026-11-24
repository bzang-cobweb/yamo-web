<?php

namespace Modules\Partner\Notifications;

use Modules\Partner\Enums\PartnerStatusEnum;

/** Un partenaire archivé (décision 2026-11-24-partenaire-notification-par-statut). */
final class PartnerArchived extends PartnerNotification
{
    public static function status(): PartnerStatusEnum
    {
        return PartnerStatusEnum::ARCHIVED;
    }
}
