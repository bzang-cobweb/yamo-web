<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Présentation de l'application (page d'accueil).
 *
 * @property Setting $resource
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array{name: string, logo_url: string|null, description: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            /** Nom de l'application (`APP_NAME`). */
            'name' => (string) config('app.name'),
            /** Adresse du logo, ou `null` : afficher le nom à la place. */
            'logo_url' => $this->resource->logoUrl(),
            /** Description dans la langue de la requête, ou `null`. */
            'description' => $this->resource->localizedDescription(),
        ];
    }
}
