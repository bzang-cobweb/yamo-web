<?php

namespace Modules\Auth\Forms;

use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Location\Models\Region;
use Modules\Location\Support\CurrentCountry;

/**
 * Une adresse du compte, depuis la liste « Mes adresses » de l'app : vide pour
 * en ajouter une (`POST /api/v1/auth/me/addresses`), ou liée à l'une de ses
 * adresses (`?record=12`, une `UserAddress`) pour la modifier
 * (`PATCH …/addresses/12`). Destinataire (genre, prénom, nom, préremplis depuis
 * le compte à l'ajout), puis rue (avec suggestions), NPA, ville et région
 * obligatoires, et des indications facultatives. Latitude et longitude sont
 * réservées au back-office : une adresse créée depuis l'app n'a pas de coordonnées (décisions
 * 2026-11-18-adresses-liste-generique, 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-adresse-app-simplifiee).
 */
class UserAddressForm extends Form
{
    private ?UserAddress $userAddress = null;

    public static function name(): string
    {
        return 'auth.address';
    }

    /**
     * Seule une adresse du compte dans le pays courant peut être ouverte ; une
     * autre répond 404 (décision 2026-11-18-espace-pays).
     */
    public function forRecord(string $key): static
    {
        $user = $this->user instanceof User ? $this->user : null;
        $country = CurrentCountry::require();

        $this->userAddress = $user?->addresses()->with('address')
            ->whereHas('address', fn ($query) => $query->inCountry($country->getKey()))
            ->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        $recipient = $this->userAddress ?? $user;
        $address = $this->userAddress?->address;
        $country = CurrentCountry::require();

        return [
            Field::select('gender')
                ->label(__('auth::user.gender'))
                ->options(GenderEnum::options())
                ->default($recipient?->gender?->value)
                ->required(),
            Field::text('first_name')
                ->label(__('auth::user.first_name'))
                ->default($recipient?->first_name)
                ->required()
                ->maxLength(255),
            Field::text('last_name')
                ->label(__('auth::user.last_name'))
                ->default($recipient?->last_name)
                ->required()
                ->maxLength(255),
            // La rue propose les adresses connues du pays, comme pour une offre ; en choisir
            // une remplit le NPA et la ville (décision 2026-11-19-carnet-adresses-recherche).
            Field::text('street')
                ->label(__('location::address.street'))
                ->help(__('auth::address.street_help'))
                ->suggestions('api.v1.addresses.suggestions')
                ->default($address?->street)
                ->required()
                ->maxLength(255),
            Field::text('zip')
                ->label(__('location::address.zip'))
                ->default($address?->zip)
                ->required()
                ->maxLength(20),
            Field::text('city')
                ->label(__('location::address.city'))
                ->default($address?->city)
                ->required()
                ->maxLength(255),
            // La région, après la ville, une liste cherchable des régions actives du pays courant : elle donne le pays
            // de l'adresse, qui n'est plus saisi (décisions 2026-11-25-region-de-l-adresse et 2026-11-25-adresse-sans-pays).
            Field::select('region')
                ->label(__('location::address.region'))
                ->options(Region::optionsIn($country->getKey()))
                ->searchable()
                ->default($address?->region_id === null ? null : (string) $address->region_id)
                ->required(),
            // Facultatives, propres au compte (l'adresse est partagée).
            Field::textarea('indication')
                ->label(__('auth::address.indication'))
                ->placeholder(__('auth::address.indication_placeholder'))
                ->default($this->userAddress?->indication),
        ];
    }

    public function title(): string
    {
        return __('auth::address.form.'.($this->userAddress === null ? 'create_title' : 'edit_title'));
    }

    /**
     * Ajout, ou changement de l'adresse ouverte ; retour à la liste.
     */
    public function submission(): Submission
    {
        $message = __('auth::address.form.saved');

        return $this->userAddress === null
            ? Submission::toRoute('api.v1.auth.me.addresses.store', SuccessAction::BACK, $message)
            : Submission::toRoute('api.v1.auth.me.addresses.update', SuccessAction::BACK, $message, parameters: ['userAddress' => $this->userAddress->getKey()]);
    }

    public function submitLabel(): string
    {
        return __('auth::address.form.save');
    }
}
