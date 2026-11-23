<?php

namespace Modules\Partner\Http\Resources;

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

    /** Avec l'e-mail et le téléphone de contact : pour la page du partenaire seulement. */
    public function withContact(): static
    {
        $this->withContact = true;

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
     * @return array{id: string, name: string, slogan: string|null, description: string|null, url: string|null, cta_label: string|null, logo: string|null, contact_email?: string|null, contact_phone?: string|null}
     */
    public function toArray(Request $request): array
    {
        $partner = $this->resource;
        $text = fn (string $key): ?string => ($value = (string) $partner->getTranslation($key, app()->getLocale())) === '' ? null : $value;

        return [
            'id' => (string) $partner->getKey(),
            'name' => (string) $partner->getTranslation('name', app()->getLocale()),
            // Facultatif (décision 2026-11-24-partenaire-slogan).
            'slogan' => $text('slogan'),
            'description' => $text('description'),
            'url' => $text('url'),
            'cta_label' => $text('cta_label'),
            'logo' => MediaUrl::onRequestHost($partner->logoUrl()),
            ...($this->withContact ? ['contact_email' => $partner->contact_email, 'contact_phone' => $this->phone()] : []),
        ];
    }
}
