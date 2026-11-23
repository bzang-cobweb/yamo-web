<?php

namespace Modules\Partner\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire tel que l'app l'affiche, ses textes et son lien dans la langue demandée (décision
 * 2026-11-23-module-partenaires). Ni contact ni compteurs : ils restent au back-office.
 *
 * @property Partner $resource
 */
class PartnerResource extends JsonResource
{
    /**
     * @return array{id: string, name: string, description: string|null, url: string|null, cta_label: string|null, logo: string|null}
     */
    public function toArray(Request $request): array
    {
        $partner = $this->resource;
        $text = fn (string $key): ?string => ($value = (string) $partner->getTranslation($key, app()->getLocale())) === '' ? null : $value;

        return [
            'id' => (string) $partner->getKey(),
            'name' => (string) $partner->getTranslation('name', app()->getLocale()),
            'description' => $text('description'),
            'url' => $text('url'),
            'cta_label' => $text('cta_label'),
            'logo' => MediaUrl::onRequestHost($partner->logoUrl()),
        ];
    }
}
