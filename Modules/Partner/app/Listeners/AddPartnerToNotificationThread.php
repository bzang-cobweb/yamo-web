<?php

namespace Modules\Partner\Listeners;

use Illuminate\Support\Str;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerNotificationFilter;

/**
 * Sur la page d'une notification du fil d'un partenaire (`partner:group:5`), l'en-tête du partenaire (décisions
 * 2026-11-26-booking-summary et 2026-11-26-entete-partenaire) : son nom, en lien vers sa page, et son slogan dessous. Un
 * partenaire introuvable : aucun en-tête, le texte de la notification à la place.
 */
final class AddPartnerToNotificationThread
{
    public function handle(NotificationThreadSerializing $event): void
    {
        $prefix = PartnerNotificationFilter::partnerThreadPrefix();
        if (! str_starts_with($event->thread, $prefix)) {
            return;
        }

        $id = Str::after($event->thread, $prefix);
        $partner = ctype_digit($id) ? Partner::query()->find((int) $id) : null;
        if (! $partner instanceof Partner) {
            return;
        }

        $locale = app()->getLocale();
        $name = (string) $partner->getTranslation('name', $locale);
        if ($name === '') {
            return;
        }

        $slogan = (string) $partner->getTranslation('slogan', $locale);
        $event->data['heading'] = [
            'title' => $name,
            'subtitle' => $slogan === '' ? null : $slogan,
            'link' => '/partners/'.$partner->getKey(),
        ];
    }
}
