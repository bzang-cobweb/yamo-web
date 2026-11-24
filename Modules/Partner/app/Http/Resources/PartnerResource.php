<?php

namespace Modules\Partner\Http\Resources;

use App\Models\Setting;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Support\PhoneNumbers;
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
     * @return array{id: string, name: string, url: string|null, slogan: string|null, description: string|null, website: string|null, cta_label: string|null, logo: string|null, contact_email?: string|null, contact_phone?: string|null, status?: array{label: string, tone: string}}
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
     * voit qu'un partenaire affiché, il n'en a pas besoin.
     *
     * @return array{status?: array{label: string, tone: string}}
     */
    private function status(Request $request): array
    {
        if (! $this->withContact) {
            return [];
        }
        if (! $this->resource->isAdministeredBy($request->user('sanctum')?->getAuthIdentifier())) {
            return [];
        }

        return ['status' => $this->resource->statusTag(Setting::partnerPaymentRequired())];
    }
}
