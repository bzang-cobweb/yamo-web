<?php

namespace Modules\Partner\Http\Resources;

use App\Models\Setting;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Notification\Support\ActorData;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire tel que l'app l'affiche, ses textes et son lien dans la langue demandée (décision
 * 2026-11-23-module-partenaires). Sa page ajoute son e-mail et son téléphone (`withContact()`, décision
 * 2026-11-24-partenaire-contact) ; les compteurs restent au back-office.
 *
 * @property Partner $resource
 */
class PartnerResource extends JsonResource
{
    private bool $withContact = false;

    /** La conversion du logo : `home` (le carrousel de l'accueil) par défaut, `detail` pour la page. */
    private string $logoConversion = 'home';

    /** Avec l'e-mail et le téléphone de contact : pour la page du partenaire seulement. */
    public function withContact(): static
    {
        $this->withContact = true;
        // La page d'un partenaire : son logo à la taille de la page (décision 2026-11-24-partenaire-logo-conversions).
        $this->logoConversion = 'detail';

        return $this;
    }

    /** Le téléphone lisible, au format international (« +237 6 70 00 00 05 ») ; `null` sans téléphone. */
    private function phone(): ?string
    {
        $phone = $this->resource->contact_phone;
        if ($phone === null || $phone === '') {
            return null;
        }

        return PhoneNumbers::international($phone);
    }

    /**
     * @return array{id: string, name: string, url: string|null, slogan: string|null, description: string|null, website: string|null, cta_label: string|null, logo: string|null, contact_email?: string|null, contact_phone?: string|null, status?: array{label: string, tone: string}, views?: array{count: int, label: string}, clicks?: array{count: int, label: string}, members_label?: string, members?: list<array<string, mixed>>}
     */
    public function toArray(Request $request): array
    {
        $partner = $this->resource;
        $text = fn (string $key): ?string => ($value = (string) $partner->getTranslation($key, app()->getLocale())) === '' ? null : $value;

        return [
            'id' => (string) $partner->getKey(),
            'name' => (string) $partner->getTranslation('name', app()->getLocale()),
            // Le slug, tiré du nom (décision 2026-11-25-slug-url-offres-partenaires).
            'url' => $text('url'),
            // Facultatif (décision 2026-11-24-partenaire-slogan).
            'slogan' => $text('slogan'),
            'description' => $text('description'),
            'website' => $text('website'),
            'cta_label' => $text('cta_label'),
            'logo' => MediaUrl::onRequestHost($partner->logoUrl($this->logoConversion)),
            ...($this->withContact ? ['contact_email' => $partner->contact_email, 'contact_phone' => $this->phone()] : []),
            ...$this->status($request),
        ];
    }

    /**
     * Sa pastille, sur sa page, pour ses comptes seulement (décision 2026-11-24-partenaire-page-statut) : un visiteur ne
     * voit qu'un partenaire affiché, il n'en a pas besoin. Son nombre de vues aussi (décisions
     * 2026-11-25-partenaire-vues-sur-sa-page et 2026-11-25-vues-oeil).
     *
     * @return array{status?: array{label: string, tone: string}, views?: array{count: int, label: string}, clicks?: array{count: int, label: string}, members_label?: string, members?: list<array<string, mixed>>}
     */
    private function status(Request $request): array
    {
        if (! $this->withContact) {
            return [];
        }
        if (! $this->resource->isAdministeredBy($request->user('sanctum')?->getAuthIdentifier())) {
            return [];
        }

        $views = (int) $this->resource->views_count;
        $clicks = (int) $this->resource->clicks_count;
        $members = $this->members($request->user('sanctum')?->getAuthIdentifier());

        return [
            'status' => $this->resource->statusTag(Setting::partnerPaymentRequired()),
            // Un œil et le nombre ; le libellé pour les lecteurs d'écran (décision 2026-11-25-vues-oeil).
            'views' => ['count' => $views, 'label' => trans_choice('partner::partner.member.views', $views, ['count' => $views])],
            // Ses clics, pour le panneau « Statistiques » de l'app (décision 2026-11-27-fiche-statistiques).
            'clicks' => ['count' => $clicks, 'label' => trans_choice('partner::partner.member.clicks', $clicks, ['count' => $clicks])],
            // Ses comptes, pour le panneau « Membres » de l'app (décision 2026-11-27-partenaire-membres) : « 3 membres »,
            // puis chacun, les administrateurs d'abord.
            'members_label' => trans_choice('partner::partner.member.members_count', count($members), ['count' => count($members)]),
            'members' => $members,
        ];
    }

    /**
     * Ses comptes : photo, nom, sa page, e-mail et téléphone, et la pastille « Administrateur » pour un administrateur ;
     * les administrateurs d'abord, puis par nom.
     *
     * @return list<array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string, email: string|null, phone: string|null, is_admin: bool, can_change_admin: bool, can_remove: bool, tag: array{label: string, tone: string}|null}>
     */
    private function members(int|string|null $readerId): array
    {
        // Un administrateur change `is_admin` des autres, pas le sien (décision 2026-11-27-membre-administrateur).
        $manager = $this->resource->hasAdmin($readerId);

        $users = $this->resource->users()->get()
            ->sortBy(fn (User $user): array => [$user->pivot->getAttribute('is_admin') ? 0 : 1, mb_strtolower((string) $user->full_name)])
            ->values();

        return $users->map(fn (User $user): array => [
            ...ActorData::from($user),
            // Ses coordonnées, pour le panneau d'un membre : entre comptes d'un même partenaire (décision
            // 2026-11-27-partenaire-membre-panneau).
            'email' => $user->email,
            'phone' => $user->phone === null || $user->phone === '' ? null : PhoneNumbers::international($user->phone),
            'is_admin' => (bool) $user->pivot->getAttribute('is_admin'),
            // Les mêmes, pour le retirer (décision 2026-11-27-membre-retirer).
            'can_change_admin' => $manager && (string) $user->getKey() !== (string) $readerId,
            'can_remove' => $manager && (string) $user->getKey() !== (string) $readerId,
            // En vert (décision 2026-11-27-partenaire-membre-panneau).
            'tag' => $user->pivot->getAttribute('is_admin') ? ['label' => (string) __('partner::partner.fields.is_admin'), 'tone' => 'success'] : null,
        ])->all();
    }
}
