<?php

namespace Modules\Location\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Location\Models\Address;
use Modules\Location\Services\AddressService;

/**
 * Posée sur la rue : refuse une adresse qui existe déjà dans la région (même rue,
 * NPA et ville, sans casse ni espaces en trop), car une adresse n'existe qu'une
 * fois. `$ignore` est l'adresse modifiée, qui peut garder ses valeurs.
 */
class UniqueAddress implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $regionId,
        private readonly ?string $zip,
        private readonly ?string $city,
        private readonly ?Address $ignore = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Sans région, la règle « obligatoire » de la région répond.
        if (blank($this->regionId)) {
            return;
        }

        $existing = app(AddressService::class)->find([
            'region_id' => $this->regionId,
            'street' => (string) $value,
            'zip' => (string) $this->zip,
            'city' => (string) $this->city,
        ]);

        if ($existing !== null && ! $existing->is($this->ignore)) {
            $fail(__('location::address.duplicate'));
        }
    }
}
