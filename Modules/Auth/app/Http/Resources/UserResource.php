<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, first_name: string, last_name: string, email: string, locale: string, avatar_url: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'locale' => $this->locale,
            'avatar_url' => $this->avatarUrlFor($request),
        ];
    }

    /**
     * Vignette de la photo, sur l'hôte de la requête : l'app joint l'API par
     * l'adresse qu'elle connaît (en local, HTTP), quel que soit `APP_URL`.
     */
    private function avatarUrlFor(Request $request): ?string
    {
        $url = $this->resource->avatarUrl();

        return $url === null ? null : $request->getSchemeAndHttpHost().parse_url($url, PHP_URL_PATH);
    }
}
