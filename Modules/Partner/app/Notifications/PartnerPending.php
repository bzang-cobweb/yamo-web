<?php

namespace Modules\Partner\Notifications;

use Modules\Partner\Enums\PartnerStatusEnum;

/** Un partenaire en validation (décision 2026-11-24-partenaire-notification-par-statut). */
final class PartnerPending extends PartnerNotification
{
    public static function status(): PartnerStatusEnum
    {
        return PartnerStatusEnum::PENDING;
    }
}
