<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Support\AuthChannels;

/**
 * Présentation de l'application (page d'accueil).
 *
 * @property Setting $resource
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array{name: string, description: string|null, channels: array{email: bool, phone: bool}}
     */
    public function toArray(Request $request): array
    {
        return [
            /** Nom de l'application (`APP_NAME`). */
            'name' => (string) config('app.name'),
            /** Description dans la langue de la requête, ou `null`. */
            'description' => $this->resource->localizedDescription(),
            /** Canaux de compte de l'application (`AUTH_CHANNEL_EMAIL`, `AUTH_CHANNEL_PHONE`) : au moins un est actif. */
            'channels' => AuthChannels::enabled(),
        ];
    }
}
