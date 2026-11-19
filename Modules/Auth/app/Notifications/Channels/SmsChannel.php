<?php

namespace Modules\Auth\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Modules\Auth\Sms\SmsSender;

/**
 * Canal SMS des notifications (décision 2026-11-19-notifications-reservations) :
 * le numéro vient de `routeNotificationFor('sms')` du destinataire, le texte de
 * `toSms()` de la notification ; l'envoi passe par `SmsSender` (le journal en
 * local, un fournisseur réel plus tard). Sans numéro ou sans texte : rien.
 */
class SmsChannel
{
    public function __construct(private readonly SmsSender $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = method_exists($notifiable, 'routeNotificationFor') ? $notifiable->routeNotificationFor('sms', $notification) : null;

        if (! is_string($to) || $to === '' || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sms->send($to, (string) $notification->toSms($notifiable));
    }
}
