<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Auth\Support\PhoneOwnership;

/**
 * Téléphone qu'aucun autre compte n'a : le numéro saisi est mis en E.164 avec le
 * pays choisi (`<champ>_country`) avant d'être comparé. Un numéro invalide est
 * laissé à la règle `phone`. Un numéro qui sera prouvé par code (`$proven`) n'est
 * refusé que si un autre compte le tient (`PhoneOwnership::isTaken`) ; un numéro
 * enregistré sans preuve, dès qu'un autre compte le porte (`isHeld`).
 */
final class UniquePhone implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly ?int $ignoreUserId = null, private readonly bool $proven = true) {}

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

        // Un compte en attente de suppression garde son numéro ; un compte inactif, ou qui ne l'a pas vérifié, le cède
        // à qui le prouve (décision 2026-11-28-numero-non-verifie).
        $taken = $this->proven
            ? PhoneOwnership::isTaken($phone, $this->ignoreUserId)
            : PhoneOwnership::isHeld($phone, $this->ignoreUserId);

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
