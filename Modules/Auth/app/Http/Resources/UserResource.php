<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\Concerns\ServesMediaOnRequestHost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    use ServesMediaOnRequestHost;

    /**
     * @return array{id: int, name: string, first_name: string, last_name: string, full_name: string, initials: string, gender: string|null, birth_date: string|null, country: array{code: string, name: string, dial_code: string}|null, region: array{id: int, name: string}|null, description: string|null, email: string|null, phone: string|null, phone_international: string|null, phone_country: string|null, last_seen_at: string|null, is_online: bool, locale: string, avatar_url: string|null}
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
            'country' => $this->countryData(),
            'region' => $this->regionData(),
            'description' => $this->resource->description,
            'email' => $this->email,
            /** E.164 (`+41791234567`), ou `null`. */
            'phone' => $this->phone,
            /** Numéro au format international lisible (`+41 79 123 45 67`), pour l'affichage. */
            'phone_international' => $this->phoneInternational(),
            /** Code ISO du pays de l'indicatif du téléphone. */
            'phone_country' => $this->phoneCountryCode(),
            'last_seen_at' => $this->lastSeenAt(),
            'is_online' => $this->resource->is_online,
            'locale' => $this->locale,
            // Le profil : sa photo à la taille du profil (décision 2026-11-24-avatar-photos-offre-conversions).
            'avatar_url' => $this->onRequestHost($this->resource->avatarUrl('profile'), $request),
        ];
    }

    /**
     * @return array{code: string, name: string, dial_code: string}|null
     */
    private function countryData(): ?array
    {
        $country = $this->resource->country;

        return $country === null ? null : ['code' => $country->code, 'name' => $country->label(), 'dial_code' => $country->dial_code];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function regionData(): ?array
    {
        $region = $this->resource->region;

        return $region === null ? null : ['id' => $region->getKey(), 'name' => (string) $region->name];
    }

    private function phoneInternational(): ?string
    {
        return $this->resource->phone === null ? null : PhoneNumbers::international($this->resource->phone);
    }

    private function phoneCountryCode(): ?string
    {
        return $this->resource->phoneCountry?->code;
    }

    private function lastSeenAt(): ?string
    {
        return $this->resource->last_seen_at?->toIso8601String();
    }

    /**
     * Date de naissance seule, `AAAA-MM-JJ`, ou `null`. Méthode typée : le contrat
     * OpenAPI ne sait pas typer le cast `date` lui-même.
     */
    private function birthDate(): ?string
    {
        return $this->resource->birth_date?->format('Y-m-d');
    }
}
