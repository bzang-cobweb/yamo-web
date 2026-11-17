<?php

namespace Modules\Location\Forms;

use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Country;
use Modules\Location\Models\UserAddress;

/**
 * Une adresse du compte, depuis la liste « Mes adresses » de l'app : vide pour
 * en ajouter une (`POST /api/v1/auth/me/addresses`), ou liée à l'une de ses
 * adresses (`?record=12`, une `UserAddress`) pour la modifier
 * (`PATCH …/addresses/12`). Destinataire (genre, prénom, nom, préremplis depuis
 * le compte à l'ajout), puis pays, rue, NPA et ville obligatoires. Latitude,
 * longitude et suggestions de rue sont réservées au back-office : une adresse
 * créée depuis l'app n'a pas de coordonnées (décisions
 * 2026-11-18-adresses-liste-generique, 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-adresse-app-simplifiee).
 */
class AddressForm extends Form
{
    private ?UserAddress $userAddress = null;

    public static function name(): string
    {
        return 'location.address';
    }

    /**
     * Seule une adresse du compte peut être ouverte ; une autre répond 404.
     */
    public function forRecord(string $key): static
    {
        $user = $this->user instanceof User ? $this->user : null;

        $this->userAddress = $user?->addresses()->with('address')->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        $recipient = $this->userAddress ?? $user;
        $address = $this->userAddress?->address;

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
            Field::select('country_id')
                ->label(__('location::address.country'))
                ->options(Country::options())
                ->default((string) ($address?->country_id ?? SingleCountry::soleActiveId() ?? '') ?: null)
                ->required(),
            Field::text('street')
                ->label(__('location::address.street'))
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
        ];
    }

    public function title(): string
    {
        return __('location::address.form.'.($this->userAddress === null ? 'create_title' : 'edit_title'));
    }

    /**
     * Ajout, ou changement de l'adresse ouverte ; retour à la liste.
     */
    public function submission(): Submission
    {
        $message = __('location::address.form.saved');

        return $this->userAddress === null
            ? Submission::toRoute('api.v1.auth.me.addresses.store', SuccessAction::BACK, $message)
            : Submission::toRoute('api.v1.auth.me.addresses.update', SuccessAction::BACK, $message, parameters: ['userAddress' => $this->userAddress->getKey()]);
    }

    public function submitLabel(): string
    {
        return __('location::address.form.save');
    }
}
