<?php

namespace Modules\Partner\Notifications;

use Modules\Partner\Enums\PartnerStatusEnum;

/** Un partenaire en attente de paiement (décision 2026-11-24-partenaire-attente-paiement). */
final class PartnerAwaitingPayment extends PartnerNotification
{
    public static function status(): PartnerStatusEnum
    {
        return PartnerStatusEnum::AWAITING_PAYMENT;
    }
}
