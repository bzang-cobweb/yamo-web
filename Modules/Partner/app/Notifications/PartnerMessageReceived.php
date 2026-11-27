<?php

namespace Modules\Partner\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Partner\Forms\PartnerMessageForm;
use Modules\Partner\Models\Partner;

/**
 * Un message d'un membre d'un partenaire aux autres membres, dans le fil du partenaire (décision
 * 2026-11-27-partenaire-messages) : comme ses autres notifications, une seule dans l'onglet, à un membre qui n'est pas
 * l'auteur ; ses autres administrateurs par e-mail et SMS (`$inTab` faux). L'auteur (`user_id`) et tous les membres le
 * lisent dans le fil, en bulles (`message`) ; « Écrire » rouvre le formulaire `partner.message`.
 */
class PartnerMessageReceived extends Notification implements HasNotificationThread, HasNotificationUser, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Partner $partner,
        public readonly User $sender,
        public readonly string $text,
        public readonly bool $inTab = true,
    ) {
        $this->afterCommit();
    }

    /** Le formulaire pour écrire dans le fil du partenaire `$partnerId`. */
    public static function writeLink(int $partnerId): string
    {
        return '/profile/'.PartnerMessageForm::name().'?record='.$partnerId;
    }

    /**
     * Les données d'un message, dans la langue courante : « Message », le texte (aussi celui de la carte), le partenaire
     * et « Écrire ».
     *
     * @return array{title: string, body: string, summary: string, message: string, partner_id: int, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public static function data(Partner $partner, string $text): array
    {
        return [
            'title' => __('partner::partner.message.title'),
            'body' => $text,
            'summary' => $text,
            'message' => $text,
            'partner_id' => (int) $partner->getKey(),
            'link' => self::writeLink((int) $partner->getKey()),
            'link_label' => __('partner::partner.message.write'),
            'text_links' => [],
            'links' => [],
        ];
    }

    /**
     * Dans l'onglet, écrite avant la réponse de l'API : l'auteur retrouve son message dans le fil dès son retour.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        $channels = $notifiable->notificationChannels();

        return $this->inTab ? ['database', ...$channels] : $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return self::data($this->partner, $this->text);
    }

    public function notificationThread(object $notifiable): ?string
    {
        return $this->partner->groupThread();
    }

    public function notificationUserId(object $notifiable): ?int
    {
        return (int) $this->sender->getKey();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replace = $this->replacements();

        return (new MailMessage)
            ->subject(__('partner::partner.message.subject', $replace))
            ->greeting(__('partner::partner.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line(__('partner::partner.message.intro', $replace))
            ->line($this->text)
            ->line(__('partner::partner.message.open_app', $replace))
            ->salutation(__('partner::partner.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return __('partner::partner.message.sms', $this->replacements());
    }

    /**
     * @return array{app: string, name: string, partner: string}
     */
    private function replacements(): array
    {
        return [
            'app' => (string) config('app.name'),
            'name' => (string) $this->sender->first_name,
            'partner' => (string) $this->partner->getTranslation('name', app()->getLocale()),
        ];
    }
}
