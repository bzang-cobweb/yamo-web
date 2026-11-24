<?php

namespace Modules\Partner\Notifications;

use Modules\Partner\Enums\PartnerStatusEnum;

/** Un partenaire accepté (publié) (décision 2026-11-24-partenaire-notification-par-statut). */
final class PartnerPublished extends PartnerNotification
{
    public static function status(): PartnerStatusEnum
    {
        return PartnerStatusEnum::PUBLISHED;
    }
}
