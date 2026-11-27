<?php

namespace Modules\Auth\Enums;

/**
 * Le type de la pièce d'identité envoyée (décision 2026-11-27-identite-pays-et-type-de-piece).
 */
enum IdentityDocumentType: string
{
    case DRIVING_LICENCE = 'driving_licence';
    case PASSPORT = 'passport';
    case IDENTITY_CARD = 'identity_card';

    public function label(): string
    {
        return __('auth::identity.document_types.'.$this->value);
    }

    /**
     * Valeur => libellé traduit, pour un champ de formulaire.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
