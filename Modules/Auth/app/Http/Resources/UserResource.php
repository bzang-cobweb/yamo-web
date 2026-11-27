<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\Concerns\ServesMediaOnRequestHost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\Auth\Support\Masks;
use Modules\Auth\Support\PhoneNumbers;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    use ServesMediaOnRequestHost;

    /**
     * @return array{id: int, name: string, first_name: string, last_name: string, full_name: string, initials: string, gender: string|null, birth_date: string|null, country: array{code: string, name: string, dial_code: string}|null, region: array{id: int, name: string}|null, description: string|null, email: string|null, phone: string|null, phone_international: string|null, phone_country: string|null, email_masked: string|null, phone_masked: string|null, email_verified: bool, phone_verified: bool, last_seen_at: string|null, is_online: bool, locale: string, avatar_url: string|null}
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
            /** L'e-mail en partie masqué (« j•••@example.com »), pour l'affichage (décision 2026-11-27-email-telephone-masques). */
            'email_masked' => Masks::email($this->resource->email),
            /** Le numéro en partie masqué (« +41 ••• 67 »), pour l'affichage. */
            'phone_masked' => Masks::phone($this->resource->phone),
            /** L'e-mail est vérifié (un code reçu). */
            'email_verified' => $this->resource->email_verified_at !== null,
            /** Le numéro est vérifié (un code reçu). */
            'phone_verified' => $this->resource->phone_verified_at !== null,
            'last_seen_at' => $this->lastSeenAt(),
            'is_online' => $this->resource->is_online,
            'locale' => $this->locale,
            // Le profil : sa photo en grand (décision 2026-11-24-avatar-list-detail).
            'avatar_url' => $this->onRequestHost($this->resource->avatarUrl('detail'), $request),
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
