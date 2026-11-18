<?php

namespace Modules\Auth\Forms\Concerns;

use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Support\Field;
use Modules\Location\Support\CurrentCountry;

/**
 * Identifiant du compte (connexion, mot de passe oublié), selon les canaux de
 * l'application :
 * - l'e-mail seul : un champ e-mail (clavier e-mail) ;
 * - le téléphone seul : le pays et le numéro sur une ligne (`phone`, `phone_country`) ;
 * - les deux : un sélecteur « E-mail | Téléphone » (`channel`) et le seul champ choisi
 *   (décision 2026-11-20-pays-unique-identifiant-double).
 */
trait HasIdentifierField
{
    /**
     * @return list<Field>
     */
    protected function identifierFields(): array
    {
        $email = Field::email('email')->label(__('auth::user.email'))->required()->rules(['max:255']);
        $phone = Field::phone('phone')->label(__('auth::user.phone'))->options(PhoneNumbers::countryOptions())->required()
            // Le pays courant d'office (décision 2026-11-18-espace-pays).
            ->default(['country' => CurrentCountry::get()?->code, 'number' => null]);

        if (! AuthChannels::both()) {
            return [AuthChannels::email() ? $email : $phone];
        }

        return [
            Field::segmented('channel')
                ->label(__('auth::user.identifier'))
                ->options(['email' => __('auth::user.channel_email'), 'phone' => __('auth::user.channel_phone')])
                ->default('email')
                ->required(),
            $email->visibleWhen('channel', 'email'),
            $phone->visibleWhen('channel', 'phone'),
        ];
    }
}
