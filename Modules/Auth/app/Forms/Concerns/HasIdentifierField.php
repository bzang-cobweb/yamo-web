<?php

namespace Modules\Auth\Forms\Concerns;

use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Support\Field;

/**
 * Identifiant du compte (connexion, mot de passe oublié), selon les canaux de
 * l'application :
 * - les deux : un texte, e-mail ou téléphone au format international ;
 * - l'e-mail seul : un champ e-mail (clavier e-mail) ;
 * - le téléphone seul : un champ téléphone, le pays et le numéro sur une ligne
 *   (`identifier` et `identifier_country`).
 */
trait HasIdentifierField
{
    protected function identifierField(): Field
    {
        if (AuthChannels::both()) {
            return Field::text('identifier')
                ->label(__('auth::user.identifier'))
                ->help(__('auth::form.login.identifier_help'))
                ->required()
                ->rules(['max:255']);
        }

        if (AuthChannels::email()) {
            return Field::email('identifier')
                ->label(__('auth::user.email'))
                ->required()
                ->rules(['max:255']);
        }

        return Field::phone('identifier')
            ->label(__('auth::user.phone'))
            ->options(PhoneNumbers::countryOptions())
            ->required();
    }
}
