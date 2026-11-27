<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserPhoneService;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Un numéro supplémentaire qu'on peut ajouter (décision 2026-11-27-plusieurs-numeros) : ni le numéro principal du
 * compte, ni un de ses numéros, ni un numéro tenu par un autre compte. Le numéro saisi est mis en E.164 avec le pays
 * choisi (`<champ>_country`) ; un numéro invalide est laissé à la règle `phone`.
 */
final class AvailableExtraPhone implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly User $user) {}

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

        $refusal = UserPhoneService::refusal($this->user, $phone);

        if ($refusal !== null) {
            $fail($refusal);
        }
    }
}
