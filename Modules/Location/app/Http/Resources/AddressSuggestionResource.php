<?php

namespace Modules\Location\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Location\Models\Address;

/**
 * Une adresse suggérée pendant la saisie d'une rue (décision
 * 2026-11-19-contrat-adresse-offre-recherche) : la rue, son libellé, et les champs
 * qu'elle remplit. Jamais une personne ni un identifiant.
 *
 * @property Address $resource
 */
class AddressSuggestionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, fill: array{zip: string, city: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            /** La rue, qui remplit le champ saisi. */
            'value' => (string) $this->resource->street,
            /** « Rue du Lac 4 — 1200 Genève ». */
            'label' => $this->resource->suggestionLabel(),
            /** Les autres champs qu'elle remplit. */
            'fill' => ['zip' => (string) $this->resource->zip, 'city' => (string) $this->resource->city],
        ];
    }
}
