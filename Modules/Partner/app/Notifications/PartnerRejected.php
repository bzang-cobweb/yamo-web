<?php

namespace Modules\Partner\Notifications;

use Modules\Partner\Enums\PartnerStatusEnum;

/** Un partenaire refusé (décision 2026-11-24-partenaire-notification-par-statut). */
final class PartnerRejected extends PartnerNotification
{
    public static function status(): PartnerStatusEnum
    {
        return PartnerStatusEnum::REJECTED;
    }
}
