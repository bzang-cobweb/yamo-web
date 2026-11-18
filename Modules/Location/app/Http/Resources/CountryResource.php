<?php

namespace Modules\Location\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Location\Models\Country;

/**
 * Un pays proposé à l'entrée de l'app (décision 2026-11-18-espace-pays).
 *
 * @property Country $resource
 */
class CountryResource extends JsonResource
{
    /**
     * @return array{code: string, name: string, native_name: string, flag: string, currency: string|null, dial_code: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->resource->code,
            /** Nom dans la langue de la requête. */
            'name' => (string) $this->resource->getTranslation('name', app()->getLocale()),
            'native_name' => $this->resource->native_name,
            /** Drapeau (emoji). */
            'flag' => $this->resource->flag(),
            /** Devise (ISO 4217). */
            'currency' => $this->resource->currency,
            'dial_code' => $this->resource->dial_code,
        ];
    }
}
