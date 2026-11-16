<?php

namespace Modules\Auth\Enums;

/**
 * Genre d'un utilisateur (décision 2026-11-16-contrat-genre-date-naissance).
 */
enum GenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public function label(): string
    {
        return __('auth::user.genders.'.$this->value);
    }

    /**
     * Valeur => libellé traduit, pour un champ de formulaire.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $gender): array => [$gender->value => $gender->label()])
            ->all();
    }
}
