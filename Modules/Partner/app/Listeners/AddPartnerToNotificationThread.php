<?php

namespace Modules\Partner\Listeners;

use Bza\Form\Support\DisplayTime;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerMessageReceived;
use Modules\Partner\Notifications\PartnerNotification;
use Modules\Partner\Notifications\PartnerNotificationFilter;

/**
 * Sur la page d'une notification du fil d'un partenaire (`partner:group:5`), l'en-tête du partenaire (décisions
 * 2026-11-26-booking-summary et 2026-11-26-entete-partenaire) : son nom, en lien vers sa page (décision 2026-11-26-entete-partenaire-sans-slogan). Un
 * partenaire introuvable : aucun en-tête, le texte de la notification à la place. Sous l'en-tête, ses dernières
 * modifications (décision 2026-11-26-partenaire-dernieres-modifications).
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

        // « Écrire » à la place de « Mes partenaires », pour un membre qui peut écrire aux autres (décision
        // 2026-11-27-partenaire-messages) : le formulaire de message, dans un panneau de l'app.
        $reader = request()->user();
        if ($reader instanceof User && Gate::forUser($reader)->allows('message', $partner)) {
            $event->data['link'] = PartnerMessageReceived::writeLink((int) $partner->getKey());
            $event->data['link_label'] = (string) __('partner::partner.message.write');
        }

        // « Info » ouvre la fiche du partenaire dans un panneau, à la place de la fiche de la notification (décision
        // 2026-11-27-info-partenaire-en-panneau).
        $event->data['menu'] = array_map(
            fn (array $entry): array => $entry['key'] === 'info' ? [...$entry, 'key' => 'partner', 'record' => (string) $partner->getKey()] : $entry,
            $event->data['menu'] ?? [],
        );
        // Le fil d'un groupe : « Membres », ses comptes (décision 2026-11-27-partenaire-membres), après « Info ».
        $event->data['menu'][] = ['key' => 'members', 'label' => (string) __('partner::partner.member.members'), 'link' => null, 'record' => (string) $partner->getKey(), 'empty' => null];

        // Son nom seul, sans slogan (décision 2026-11-26-entete-partenaire-sans-slogan).
        $event->data['heading'] = [
            'title' => $name,
            'subtitle' => null,
            'link' => '/partners/'.$partner->getKey(),
        ];

        // Sous l'en-tête, ses dernières modifications depuis l'app, deux lignes par champ : « Slogan (avant) », « Slogan
        // (après) » (décisions 2026-11-26-partenaire-dernieres-modifications et 2026-11-26-modifications-avant-apres) ;
        // aucune : pas de lignes.
        $last = is_array($partner->last_changes) ? $partner->last_changes : [];
        $changes = is_array($last['changes'] ?? null) ? $last['changes'] : [];
        $lines = [];
        foreach ($changes as $change) {
            if (! is_array($change) || ! is_string($change['field'] ?? null)) {
                continue;
            }
            array_push($lines, ...PartnerNotification::changeLines($change['field'], (string) ($change['old'] ?? ''), (string) ($change['new'] ?? '')));
        }
        if ($lines !== []) {
            // En tête, quand et par qui (décision 2026-11-26-modifications-date-auteur), puis les champs changés.
            $event->data['lines'] = [...$this->when($last), ...$this->who($last), ...$lines];
        }
        // Une entrée du menu ⋮ les ouvre dans un panneau, titré de son libellé (décision
        // 2026-11-27-menu-dernieres-modifications) ; toujours là, même sans modification : le panneau dit alors `empty`
        // (décision 2026-11-27-menu-entrees-vides).
        $event->data['menu'][] = [
            'key' => 'lines',
            'label' => (string) __('partner::partner.notify.changes.menu'),
            'link' => null,
            'record' => null,
            'empty' => (string) __('partner::partner.notify.changes.none'),
        ];
    }

    /**
     * « Dernière modification » : la date et l'heure de la modification (« 26 nov. 2026 16:31 ») ; rien sans date.
     *
     * @param  array<string, mixed>  $last
     * @return list<array{key: string, label: string, value: string}>
     */
    private function when(array $last): array
    {
        $at = is_string($last['at'] ?? null) ? DisplayTime::date($last['at'], 'll LT') : null;

        return $at === null ? [] : [['key' => 'changed_at', 'label' => (string) __('partner::partner.notify.changes.at'), 'value' => $at]];
    }

    /**
     * « Modifié par » : le nom du compte, lu à l'affichage, en lien vers sa page ; rien si le compte n'existe plus.
     *
     * @param  array<string, mixed>  $last
     * @return list<array{key: string, label: string, value: string, link: string}>
     */
    private function who(array $last): array
    {
        $author = is_int($last['by'] ?? null) ? User::query()->find($last['by']) : null;
        if (! $author instanceof User) {
            return [];
        }

        return [['key' => 'changed_by', 'label' => (string) __('partner::partner.notify.changes.by'), 'value' => (string) $author->full_name, 'link' => '/members/'.$author->getKey()]];
    }
}
