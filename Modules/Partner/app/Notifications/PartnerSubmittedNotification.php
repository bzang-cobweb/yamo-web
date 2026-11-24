<?php

namespace Modules\Partner\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Lists\MyPartnerListing;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire a été ajouté ou modifié depuis l'app et attend la validation de l'équipe (décision
 * 2026-11-24-partenaires-validation-notifications) : l'auteur (« Votre partenaire … est en validation ») et chaque
 * autre compte rattaché (« … modifié par … ») le lisent dans l'onglet « Notifications », et par e-mail et SMS selon
 * leurs canaux ; elle ouvre « Mes partenaires ». Textes dans la langue du destinataire.
 */
final class PartnerSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Partner $partner,
        public readonly int|string $actorId,
        public readonly string $actorName,
        public readonly bool $created,
    ) {
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
     * @return array{title: string, body: string, own: bool, partner_id: int, partner_status: string, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body($notifiable),
            'own' => $this->isOwn($notifiable),
            'partner_id' => (int) $this->partner->getKey(),
            // Le statut annoncé : en validation (le filtre « Statut du partenaire », décision
            // 2026-11-24-partenaires-validation-notifications).
            'partner_status' => PartnerStatusEnum::PENDING->value,
            'link' => '/profile/lists/'.MyPartnerListing::name(),
            'link_label' => __('partner::partner.member.mine'),
            'text_links' => [],
            'links' => [],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting(__('partner::partner.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line($this->body($notifiable))
            ->salutation(__('partner::partner.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return $this->body($notifiable);
    }

    /** « Partenaire ajouté » ou « Partenaire modifié ». */
    private function title(): string
    {
        return __('partner::partner.notify.submitted.title.'.$this->action());
    }

    /**
     * À l'auteur : « Votre partenaire « … » est en validation… » ; aux autres : « « … » a été modifié par … ; il est en
     * validation. »
     */
    private function body(object $notifiable): string
    {
        $name = (string) $this->partner->getTranslation('name', app()->getLocale());
        if ($this->isOwn($notifiable)) {
            return __('partner::partner.notify.submitted.own.'.$this->action(), ['name' => $name]);
        }

        return __('partner::partner.notify.submitted.others.'.$this->action(), ['name' => $name, 'actor' => $this->actorName]);
    }

    private function action(): string
    {
        return $this->created ? 'created' : 'updated';
    }

    private function isOwn(object $notifiable): bool
    {
        return $notifiable instanceof User && (string) $notifiable->getKey() === (string) $this->actorId;
    }
}
