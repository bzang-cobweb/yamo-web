<?php

namespace Modules\Partner\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Partner\Lists\MyPartnerListing;
use Modules\Partner\Models\Partner;

/**
 * Le statut d'un partenaire a changé (décision 2026-11-24-partenaires-notifications) : chaque compte qui lui est
 * rattaché, administrateur ou non, le lit dans l'onglet « Notifications », et par e-mail et SMS selon ses canaux ;
 * elle ouvre « Mes partenaires ». Textes dans la langue du destinataire.
 */
final class PartnerStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Partner $partner)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['database', ...$notifiable->notificationChannels()] : [];
    }

    /**
     * @return array{title: string, body: string, partner_id: int, partner_status: string, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('partner::partner.notify.title.'.$this->status()),
            'body' => $this->body(),
            'partner_id' => (int) $this->partner->getKey(),
            'partner_status' => $this->status(),
            'link' => '/profile/lists/'.MyPartnerListing::name(),
            'link_label' => __('partner::partner.member.mine'),
            'text_links' => [],
            'links' => [],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('partner::partner.notify.title.'.$this->status()))
            ->greeting(__('partner::partner.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line($this->body())
            ->salutation(__('partner::partner.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return $this->body();
    }

    private function status(): string
    {
        return $this->partner->status->value;
    }

    /** « Votre partenaire « Déménageurs du Wouri » est publié. » */
    private function body(): string
    {
        return __('partner::partner.notify.body.'.$this->status(), ['name' => (string) $this->partner->getTranslation('name', app()->getLocale())]);
    }
}
