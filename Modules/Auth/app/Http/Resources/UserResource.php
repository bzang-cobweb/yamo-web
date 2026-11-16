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
     * @return array{id: int, name: string, first_name: string, last_name: string, full_name: string, initials: string, gender: string|null, birth_date: string|null, email: string, locale: string, avatar_url: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'gender' => $this->gender?->value,
            'birth_date' => $this->birthDate(),
            'email' => $this->email,
            'locale' => $this->locale,
            'avatar_url' => $this->avatarUrlFor($request),
        ];
    }

    /**
     * Date de naissance seule, `AAAA-MM-JJ`, ou `null`. Méthode typée : le contrat
     * OpenAPI ne sait pas typer le cast `date` lui-même.
     */
    private function birthDate(): ?string
    {
        return $this->resource->birth_date?->format('Y-m-d');
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
