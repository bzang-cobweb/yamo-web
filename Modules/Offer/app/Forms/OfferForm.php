<?php

namespace Modules\Offer\Forms;

use App\Support\Currency;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Step;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

/**
 * Une offre du compte, depuis « Mes logements » (ou le libellé du domaine) :
 * vide pour en ajouter une (`POST /api/v1/offers`), ou liée à l'une des siennes
 * (`?record=12`) pour la modifier (`PATCH /api/v1/offers/12`). Champs communs,
 * puis les attributs du domaine actif (décision 2026-11-18-offres-et-domaines).
 * Les textes se saisissent dans la langue du compte, préremplis dans cette langue
 * ou, à défaut, une autre. Sans domaine choisi, le formulaire n'existe pas.
 */
class OfferForm extends Form
{
    private ?Offer $offer = null;

    public static function name(): string
    {
        return 'offer.offer';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    /**
     * Seule une offre du compte peut être ouverte ; une autre répond 404.
     */
    public function forRecord(string $key): static
    {
        $this->offer = Offer::query()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey())
            ->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $domain = $this->domain();
        $offer = $this->offer;
        $categories = OfferCategory::options();

        return array_values(array_filter([
            Field::text('title')
                ->label(__('offer::offer.title_field'))
                ->default($offer?->getTranslation('title', $this->locale()))
                ->required()
                ->maxLength(255),
            Field::textarea('description')
                ->label(__('offer::offer.description'))
                ->default($offer?->getTranslation('description', $this->locale()))
                ->maxLength(Field::TEXTAREA_MAX_LENGTH),
            $categories === [] ? null : Field::select('category_id')
                ->label(__('offer::offer.category'))
                ->options($categories)
                ->default($offer?->category_id === null ? null : (string) $offer->category_id),
            ! $domain->hasPrice() ? null : $this->price($domain, (string) Currency::symbol(CurrentCountry::require()->currency))
                ->default($offer?->price)
                ->required($domain->requiresPrice())
                ->rules(['min:0', 'max:100000000']),
            ! $domain->hasAddress() ? null : Field::select('address_id')
                ->label(__('offer::offer.address'))
                ->help(__('offer::offer.address_help'))
                ->options($this->addressOptions())
                ->default($offer?->address_id === null ? null : (string) $offer->address_id)
                ->required($domain->requiresAddress()),
            Field::select('status')
                ->label(__('offer::offer.status'))
                ->options(OfferStatusEnum::options())
                ->default(($offer?->status ?? OfferStatusEnum::DRAFT)->value)
                ->required(),
            ...array_map(fn (Field $field): Field => $field->default($offer?->detailIn($field->name, $this->locale())), $domain->inputAttributes(CurrentCountry::require()->currency)),
        ]));
    }

    /**
     * Le prix, avec le symbole de sa devise et son unité ; l'unité peut suivre un
     * autre champ (« FCFA / jour » si meublé, décisions 2026-11-18-unite-prix-chambres
     * et 2026-11-19-contrat-formulaire-etapes-symbole-devise).
     */
    private function price(Domain $domain, string $currency): Field
    {
        $field = Field::number('price')->label($domain->priceLabel());
        $unit = $domain->priceUnitWhen();

        if ($unit === null) {
            return $field->suffix($currency);
        }

        return $field
            ->suffix($currency.' / '.$unit['default'])
            ->suffixWhen($unit['field'], array_map(fn (string $value): string => $currency.' / '.$value, $unit['units']));
    }

    /**
     * Les étapes déclarées par le domaine (décision
     * 2026-11-19-contrat-formulaire-etapes-symbole-devise), réduites aux champs
     * présents ; un champ qu'aucune étape ne cite rejoint la dernière. Sans étapes
     * déclarées, le formulaire reste d'un seul tenant.
     *
     * @return list<Step>
     */
    public function steps(): array
    {
        $declared = $this->domain()->formSteps();

        if ($declared === []) {
            return [];
        }

        $present = array_map(fn (Field $field): string => $field->name, $this->fields());
        $steps = [];
        $placed = [];

        foreach ($declared as $step) {
            $names = array_values(array_intersect($step['fields'], $present));
            $placed = [...$placed, ...$names];
            $steps[] = ['title' => $step['title'], 'fields' => $names];
        }

        $last = count($steps) - 1;
        $steps[$last]['fields'] = [...$steps[$last]['fields'], ...array_values(array_diff($present, $placed))];

        return array_values(array_map(
            fn (array $step): Step => Step::make($step['title'], $step['fields']),
            array_filter($steps, fn (array $step): bool => $step['fields'] !== []),
        ));
    }

    public function title(): string
    {
        return __('offer::offer.form.'.($this->offer === null ? 'create_title' : 'edit_title'), ['label' => $this->domain()->label()]);
    }

    public function submission(): Submission
    {
        $message = __('offer::offer.form.saved');

        return $this->offer === null
            ? Submission::toRoute('api.v1.offers.store', SuccessAction::BACK, $message)
            : Submission::toRoute('api.v1.offers.update', SuccessAction::BACK, $message, parameters: ['offer' => $this->offer->getKey()]);
    }

    public function submitLabel(): string
    {
        return __('offer::offer.form.save');
    }

    /**
     * Langue de saisie : celle du compte (décision 2026-11-18-offres-multilingues-categories-arbre).
     */
    private function locale(): string
    {
        return $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
    }

    public function domain(): Domain
    {
        return app(DomainRegistry::class)->require();
    }

    /**
     * Les adresses du compte dans le pays courant, sans doublon : id de l'adresse => « Rue du Lac 4, 1200 Genève ».
     *
     * @return array<string, string>
     */
    private function addressOptions(): array
    {
        $user = $this->user instanceof User ? $this->user : null;

        return $user === null ? [] : $user->addresses()->with('address')
            ->whereHas('address', fn ($query) => $query->where('country_id', CurrentCountry::require()->getKey()))
            ->get()
            ->unique('address_id')
            ->mapWithKeys(fn (UserAddress $entry): array => [(string) $entry->address_id => $entry->address->street.', '.$entry->address->zip.' '.$entry->address->city])
            ->all();
    }
}
