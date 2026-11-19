<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Une date de naissance qui donne au moins `$age` ans aujourd'hui (décision
 * 2026-11-19-age-minimum). Une date illisible est laissée à la règle `date`.
 */
final class MinimumAge implements ValidationRule
{
    public function __construct(private readonly int $age) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        try {
            $birthDate = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return;
        }

        if ($birthDate->greaterThan(today()->subYears($this->age))) {
            $fail('auth::form.birth_date.too_young')->translate(['age' => $this->age]);
        }
    }
}
