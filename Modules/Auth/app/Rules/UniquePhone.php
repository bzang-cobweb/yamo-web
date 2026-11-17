<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Téléphone qu'aucun autre compte n'a : le numéro saisi est mis en E.164 avec le
 * pays choisi (`<champ>_country`) avant d'être comparé. Un numéro invalide est
 * laissé à la règle `phone`.
 */
final class UniquePhone implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly ?int $ignoreUserId = null) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $country = data_get($this->data, $attribute.'_country');

        if (! is_string($value) || ! is_string($country) || $value === '' || $country === '') {
            return;
        }

        try {
            $phone = PhoneNumbers::toE164($value, $country);
        } catch (\Throwable) {
            return;
        }

        // Un compte supprimé, en attente de purge, garde son numéro.
        $taken = User::withTrashed()->where('phone', $phone)
            ->when($this->ignoreUserId !== null, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
