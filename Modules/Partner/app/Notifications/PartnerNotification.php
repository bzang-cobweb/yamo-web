<?php

namespace Modules\Partner\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Lists\MyPartnerListing;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire notifié à ses comptes (décisions 2026-11-24-partenaires-notifications,
 * 2026-11-24-partenaire-notification-par-statut, 2026-11-24-partenaire-demandes et 2026-11-24-partenaire-fil). Une classe par statut :
 * `PartnerPending`, `PartnerAwaitingPayment`, `PartnerPublished`, `PartnerRejected` ; le statut est aussi dans
 * `data.partner_status`. Dans l'onglet « Notifications », et par e-mail et SMS selon les canaux du destinataire, dans
 * sa langue ; elle ouvre « Mes partenaires ».
 *
 * - `$action` : `created` (ajouté) ou `updated` (modifié) ; `null` : un changement de statut.
 * - `$actorId` : le compte qui a agi depuis l'app ; l'auteur lit son texte (« Votre partenaire … est en validation »),
 *   les autres le leur (« … modifié par … ») et voient son nom sur la carte. `null` : l'équipe, au back-office.
 *
 * Toutes les notifications d'un partenaire font un seul fil (`Partner::groupThread()`, `partner:group:5`), lu par
 * tous ses comptes quel que soit le destinataire (décision 2026-11-24-notifications-fil-de-groupe) : une carte, dont
 * la page montre le suivi (ajouté, modifié, accepté…). Chacun la lit rédigée pour lui (`toArray()` pour le lecteur).
 */
abstract class PartnerNotification extends Notification implements HasNotificationThread, HasNotificationUser, ShouldQueue
{
    use Queueable;

    /** Une action : ajouté. */
    public const CREATED = 'created';

    /** Une action : modifié. */
    public const UPDATED = 'updated';

    /** Le statut notifié par la classe. */
    abstract public static function status(): PartnerStatusEnum;

    /** La notification du statut `$status`, de sa classe. */
    /**
     * @param  list<array{field: string, old: string, new: string}>  $changes  une modification : les champs changés
     */
    public static function for(Partner $partner, PartnerStatusEnum $status, ?string $action = null, int|string|null $actorId = null, string $actorName = '', bool $inTab = true, array $changes = []): self
    {
        $class = $status->notification();

        return new $class($partner, $action, $actorId, $actorName, $inTab, $changes);
    }

    /**
     * Le type d'une notification, sur sa carte et dans le suivi, dans la langue courante : « Partenaire ajouté » ou
     * « Partenaire modifié » pour une action (`$action`) ; sinon le statut : « Partenaire accepté ».
     */
    public static function title(PartnerStatusEnum $status, ?string $action = null): string
    {
        if ($action === self::CREATED || $action === self::UPDATED) {
            return __('partner::partner.notify.submitted.title.'.$action);
        }

        return __('partner::partner.notify.title.'.$status->value);
    }

    final public function __construct(
        public readonly Partner $partner,
        public readonly ?string $action = null,
        public readonly int|string|null $actorId = null,
        public readonly string $actorName = '',
        // Faux : par e-mail et SMS seulement, sans notification dans l'onglet (ses administrateurs, décision
        // 2026-11-24-partenaire-admins-email-sms).
        public readonly bool $inTab = true,
        // Une modification : les champs changés, avant et après, pour l'e-mail ; la page les lit sur le partenaire
        // (décisions 2026-11-26-partenaire-modifications et 2026-11-26-partenaire-dernieres-modifications).
        /** @var list<array{field: string, old: string, new: string}> */
        public readonly array $changes = [],
    ) {
        $this->afterCommit();
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
     * @return array{title: string, body: string, action: string|null, partner_id: int, partner_status: string, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => static::title(static::status(), $this->action),
            'body' => $this->body($notifiable),
            'action' => $this->action,
            'partner_id' => (int) $this->partner->getKey(),
            'partner_status' => static::status()->value,
            'link' => '/profile/lists/'.MyPartnerListing::name(),
            'link_label' => __('partner::partner.member.mine'),
            'text_links' => [],
            'links' => [],
        ];
    }

    /** Le fil du partenaire, partagé par tous ses comptes (décision 2026-11-24-notifications-fil-de-groupe). */
    public function notificationThread(object $notifiable): ?string
    {
        return $this->partner->groupThread();
    }

    /**
     * Qui agit depuis l'app : son nom et sa photo sur la carte, le filtre « Membre » ; il retrouve ainsi son action
     * (décision 2026-11-24-notifications-fil-de-groupe). Rien pour l'équipe.
     */
    public function notificationUserId(object $notifiable): ?int
    {
        if ($this->actorId === null) {
            return null;
        }

        return (int) $this->actorId;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(static::title(static::status(), $this->action))
            ->greeting(__('partner::partner.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line($this->body($notifiable))
            ->lineIf($this->changes !== [], (string) $this->changesText())
            ->salutation(__('partner::partner.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return $this->body($notifiable);
    }

    /**
     * - une action depuis l'app : à l'auteur « Votre partenaire « … » est en validation… », aux autres « « … » a été
     *   modifié par … » ;
     * - une action de l'équipe : « Votre partenaire « … » a été modifié par l'équipe. » ;
     * - un changement de statut : « Votre partenaire « … » a été accepté par l'équipe. »
     */
    private function body(object $notifiable): string
    {
        $name = (string) $this->partner->getTranslation('name', app()->getLocale());
        if ($this->action === null) {
            return __('partner::partner.notify.body.'.static::status()->value, ['name' => $name]);
        }
        if ($this->actorId === null) {
            return __('partner::partner.notify.team.'.$this->action, ['name' => $name]);
        }
        if ($this->isAuthor($notifiable)) {
            return __('partner::partner.notify.submitted.own.'.$this->action, ['name' => $name]);
        }

        return __('partner::partner.notify.submitted.others.'.$this->action, ['name' => $name, 'actor' => $this->actorName]);
    }

    /**
     * Les champs changés, un par ligne, dans la langue courante : « Description : « avant » → « après » » ; un champ vide
     * se dit « (vide) » ; `null` sans changement.
     */
    private function changesText(): ?string
    {
        if ($this->changes === []) {
            return null;
        }

        return implode("\n", array_map(fn (array $change): string => __('partner::partner.notify.changes.line', [
            'field' => __('partner::partner.fields.'.$change['field']),
            'value' => self::changeValue($change['old'], $change['new']),
        ]), $this->changes));
    }

    /**
     * Un champ changé, dans la langue courante : « « avant » → « après » » ; un champ vide se dit « (vide) » (décision
     * 2026-11-26-partenaire-modifications).
     */
    public static function changeValue(string $old, string $new): string
    {
        $value = fn (string $text): string => $text === '' ? (string) __('partner::partner.notify.changes.empty') : '« '.$text.' »';

        return $value($old).' → '.$value($new);
    }

    private function isAuthor(object $notifiable): bool
    {
        if ($this->actorId === null || ! $notifiable instanceof User) {
            return false;
        }

        return (string) $notifiable->getKey() === (string) $this->actorId;
    }
}
