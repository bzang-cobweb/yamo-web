<?php

namespace Modules\Partner\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Lists\MyPartnerListing;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire a changé de statut, notifié à ses comptes (décisions 2026-11-24-partenaires-notifications et
 * 2026-11-24-partenaire-notification-par-statut). Une classe par statut, comme les demandes de logement :
 * `PartnerPending` (ajouté ou modifié depuis l'app, en validation), `PartnerPublished`, `PartnerRejected`,
 * `PartnerArchived` ; le statut est aussi enregistré dans `data.partner_status`. Le filtre « Type » les regroupe
 * (`PartnerNotificationFilter`). Dans l'onglet « Notifications », et par e-mail et SMS selon les canaux du
 * destinataire, dans sa langue ; elle ouvre « Mes partenaires ».
 *
 * `$actorId` : qui a agi, depuis l'app ; l'auteur lit son propre texte (« Votre partenaire … est en validation »), les
 * autres comptes le sien (« … modifié par … ») et voient son nom sur la carte.
 */
abstract class PartnerNotification extends Notification implements HasNotificationUser, ShouldQueue
{
    use Queueable;

    /** Le statut notifié par la classe. */
    abstract public static function status(): PartnerStatusEnum;

    /** La notification du statut `$status`, de sa classe. */
    public static function for(Partner $partner, PartnerStatusEnum $status, int|string|null $actorId = null, string $actorName = '', bool $created = false): self
    {
        $class = $status->notification();

        return new $class($partner, $actorId, $actorName, $created);
    }

    /**
     * Le type d'une notification, sur sa carte, dans la langue courante : « Partenaire accepté » ; en validation,
     * l'action : « Partenaire ajouté » ou « Partenaire modifié » (`$created`, `null` : « Partenaire en validation »).
     */
    public static function title(PartnerStatusEnum $status, ?bool $created = null): string
    {
        if ($status === PartnerStatusEnum::PENDING && $created !== null) {
            return __('partner::partner.notify.submitted.title.'.($created ? 'created' : 'updated'));
        }

        return __('partner::partner.notify.title.'.$status->value);
    }

    final public function __construct(
        public readonly Partner $partner,
        public readonly int|string|null $actorId = null,
        public readonly string $actorName = '',
        public readonly bool $created = false,
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
     * @return array{title: string, body: string, own: bool, created: bool|null, partner_id: int, partner_status: string, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => static::title(static::status(), $this->created()),
            'body' => $this->body($notifiable),
            'own' => $this->isOwn($notifiable),
            'created' => $this->created(),
            'partner_id' => (int) $this->partner->getKey(),
            'partner_status' => static::status()->value,
            'link' => '/profile/lists/'.MyPartnerListing::name(),
            'link_label' => __('partner::partner.member.mine'),
            'text_links' => [],
            'links' => [],
        ];
    }

    /** Qui agit, pour les autres comptes : son nom et sa photo sur la carte, le filtre « Membre » ; rien pour l'auteur. */
    public function notificationUserId(object $notifiable): ?int
    {
        if ($this->actorId === null || $this->isOwn($notifiable)) {
            return null;
        }

        return (int) $this->actorId;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(static::title(static::status(), $this->created()))
            ->greeting(__('partner::partner.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line($this->body($notifiable))
            ->salutation(__('partner::partner.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return $this->body($notifiable);
    }

    /** Ajouté (`true`) ou modifié (`false`) depuis l'app ; `null` quand personne n'a agi depuis l'app. */
    private function created(): ?bool
    {
        if ($this->actorId === null) {
            return null;
        }

        return $this->created;
    }

    /**
     * Depuis l'app : à l'auteur « Votre partenaire « … » est en validation… », aux autres « « … » a été modifié par … » ;
     * sinon « Votre partenaire « … » a été accepté par l'équipe. »
     */
    private function body(object $notifiable): string
    {
        $name = (string) $this->partner->getTranslation('name', app()->getLocale());
        $created = $this->created();
        if ($created === null) {
            return __('partner::partner.notify.body.'.static::status()->value, ['name' => $name]);
        }

        $action = $created ? 'created' : 'updated';
        if ($this->isOwn($notifiable)) {
            return __('partner::partner.notify.submitted.own.'.$action, ['name' => $name]);
        }

        return __('partner::partner.notify.submitted.others.'.$action, ['name' => $name, 'actor' => $this->actorName]);
    }

    private function isOwn(object $notifiable): bool
    {
        if ($this->actorId === null || ! $notifiable instanceof User) {
            return false;
        }

        return (string) $notifiable->getKey() === (string) $this->actorId;
    }
}
