<?php

namespace Modules\Offer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\User;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Offer\Forms\OfferMessageForm;
use Modules\Offer\Models\Offer;

/**
 * Un message au sujet d'une offre, entre un membre et son éditeur (décision 2026-11-26-messages-editeur) : une
 * notification par message, envoyée à l'autre partie, dans le fil `offer_message:{offre}:{membre}` (le membre est
 * toujours celui qui n'est pas l'éditeur). L'expéditeur en est l'auteur (`user_id`) et la retrouve dans son fil ; la
 * page de la notification montre tous les messages en bulles (`message`), et « Répondre » rouvre le formulaire
 * `offer.message` avec le `record` qui convient au lecteur.
 */
class OfferMessageReceived extends Notification implements HasNotificationThread, HasNotificationUser, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Offer $offer,
        public readonly User $sender,
        public readonly User $member,
        public readonly string $text,
    ) {
        // La connexion de file n'attend pas la fin des transactions : la notification, si.
        $this->afterCommit();
    }

    /** Le fil de la discussion du membre `$memberId` sur l'offre `$offerId`. */
    public static function thread(int $offerId, int $memberId): string
    {
        // Préfixé par la clé du type « Message » (décision 2026-11-26-prefixes-de-fil-des-filtres).
        return OfferNotificationFilter::messageThreadPrefix().$offerId.':'.$memberId;
    }

    /**
     * Le formulaire de réponse pour le lecteur : l'éditeur répond au membre (`{offre}.{membre}`), le membre écrit à
     * l'éditeur (`{offre}`).
     */
    public static function replyLink(int $offerId, int $memberId, bool $forPublisher): string
    {
        return '/profile/'.OfferMessageForm::name().'?record='.$offerId.($forPublisher ? '.'.$memberId : '');
    }

    /**
     * Les données d'un message pour le lecteur, dans la langue courante : le titre « Message », le texte, l'offre
     * (son titre, dans `summary`, mène à la fiche) et « Répondre ».
     *
     * @return array{title: string, body: string, summary: string, message: string, offer_id: int, member_id: int, link: string, link_label: string, text_links: list<array{text: string, link: string}>, links: list<array{label: string, link: string}>}
     */
    public static function data(Offer $offer, int $memberId, string $text, bool $forPublisher): array
    {
        $title = (string) $offer->getTranslation('title', app()->getLocale());
        $summary = __('offer::message.notify.summary', ['offer' => $title]);

        return [
            'title' => __('offer::message.notify.title'),
            'body' => $text,
            // Sur la carte et en tête de la page : l'offre dont on parle ; le texte est dans la bulle (`message`).
            'summary' => $summary,
            'message' => $text,
            'offer_id' => (int) $offer->getKey(),
            'member_id' => $memberId,
            'link' => self::replyLink((int) $offer->getKey(), $memberId, $forPublisher),
            'link_label' => __('offer::message.notify.reply'),
            // Le titre de l'offre, dans le texte de la carte, mène à la fiche.
            'text_links' => $title !== '' && str_contains($summary, $title) ? [['text' => $title, 'link' => '/offers/'.$offer->getKey()]] : [],
            'links' => [],
        ];
    }

    /**
     * Dans l'onglet, écrite avant la réponse de l'API (file `sync`) : l'expéditeur retrouve son message dans le fil dès
     * son retour. E-mail et SMS restent en file.
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
        return $notifiable instanceof User ? ['database', ...$notifiable->notificationChannels()] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return self::data($this->offer, (int) $this->member->getKey(), $this->text, $this->isPublisher($notifiable));
    }

    public function notificationThread(object $notifiable): ?string
    {
        return self::thread((int) $this->offer->getKey(), (int) $this->member->getKey());
    }

    public function notificationUserId(object $notifiable): ?int
    {
        return (int) $this->sender->getKey();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replace = $this->replacements();

        return (new MailMessage)
            ->subject(__('offer::message.notify.subject', $replace))
            ->greeting(__('offer::booking.notify.greeting', ['name' => $notifiable instanceof User ? $notifiable->first_name : '']))
            ->line(__('offer::message.notify.intro', $replace))
            ->line(__('offer::booking.notify.message', ['message' => $this->text]))
            ->line(__('offer::message.notify.open_app', $replace))
            ->salutation(__('offer::booking.notify.salutation', ['app' => (string) config('app.name')]));
    }

    public function toSms(object $notifiable): string
    {
        return __('offer::message.notify.sms', $this->replacements());
    }

    /** Le destinataire est-il l'éditeur de l'offre ? */
    private function isPublisher(object $notifiable): bool
    {
        return $notifiable instanceof User && (int) $notifiable->getKey() === (int) $this->offer->user_id;
    }

    /**
     * @return array{app: string, name: string, offer: string}
     */
    private function replacements(): array
    {
        return [
            'app' => (string) config('app.name'),
            'name' => (string) $this->sender->first_name,
            'offer' => (string) $this->offer->getTranslation('title', app()->getLocale()),
        ];
    }
}
