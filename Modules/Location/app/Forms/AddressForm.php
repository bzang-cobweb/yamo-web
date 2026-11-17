<?php

namespace Modules\Location\Forms;

use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;

/**
 * Une adresse du compte, depuis la liste « Mes adresses » de l'app : vide pour
 * en ajouter une (`POST /api/v1/auth/me/addresses`), ou liée à l'une de ses
 * adresses (`?record=12`) pour la changer (`PATCH …/addresses/12`). Pays, rue,
 * NPA et ville obligatoires ; latitude et longitude facultatives, saisies à la
 * main (décision 2026-11-18-adresses-liste-generique).
 */
class AddressForm extends Form
{
    private ?Address $address = null;

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

        $this->address = $user?->addresses()->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $address = $this->address;

        return [
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
            Field::number('latitude')
                ->label(__('location::address.latitude'))
                ->default($address?->latitude)
                ->rules(['between:-90,90']),
            Field::number('longitude')
                ->label(__('location::address.longitude'))
                ->default($address?->longitude)
                ->rules(['between:-180,180']),
        ];
    }

    public function title(): string
    {
        return __('location::address.form.'.($this->address === null ? 'create_title' : 'edit_title'));
    }

    /**
     * Ajout, ou changement de l'adresse ouverte ; retour à la liste.
     */
    public function submission(): Submission
    {
        $message = __('location::address.form.saved');

        return $this->address === null
            ? Submission::toRoute('api.v1.auth.me.addresses.store', SuccessAction::BACK, $message)
            : Submission::toRoute('api.v1.auth.me.addresses.update', SuccessAction::BACK, $message, parameters: ['address' => $this->address->getKey()]);
    }

    public function submitLabel(): string
    {
        return __('location::address.form.save');
    }
}
