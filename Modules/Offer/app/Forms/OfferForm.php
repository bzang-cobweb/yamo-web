<?php

namespace Modules\Offer\Forms;

use App\Support\Currency;
use Modules\Auth\Models\User;
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
use Modules\Offer\Services\OfferPhotoService;

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
            // L'adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche) :
            // la rue propose les adresses existantes du pays ; à l'enregistrement, une
            // adresse identique est reprise, sinon créée. Jamais le carnet du compte.
            ! $domain->hasAddress() ? null : Field::text('street')
                ->label(__('offer::offer.street'))
                ->help(__('offer::offer.street_help'))
                ->suggestions('api.v1.addresses.suggestions')
                ->default($offer?->address?->street)
                ->required($domain->requiresAddress())
                ->maxLength(255)
                ->rules(['required_with:city']),
            ! $domain->hasAddress() ? null : Field::text('zip')
                ->label(__('offer::offer.zip'))
                ->default($offer?->address?->zip ?: null)
                ->maxLength(20),
            ! $domain->hasAddress() ? null : Field::text('city')
                ->label(__('offer::offer.city'))
                ->default($offer?->address?->city)
                ->required($domain->requiresAddress())
                ->maxLength(255)
                ->rules(['required_with:street']),
            // Indications propres à l'offre, l'adresse étant partagée (décision 2026-11-19-description-adresse-offre).
            ! $domain->hasAddress() ? null : Field::textarea('address_description')
                ->label(__('offer::offer.address_description'))
                ->placeholder(__('offer::offer.address_description_placeholder'))
                ->default($offer?->address_description)
                ->maxLength(500),
            Field::select('status')
                ->label(__('offer::offer.status'))
                ->options(OfferStatusEnum::options())
                ->default(($offer?->status ?? OfferStatusEnum::DRAFT)->value)
                ->required(),
            ...array_map(fn (Field $field): Field => $field->default($offer?->detailIn($field->name, $this->locale())), $domain->inputAttributes(CurrentCountry::require()->currency)),
            // Les photos, au moins une (décision 2026-11-19-contrat-champ-upload-photos).
            Field::upload('photos')
                ->label(__('offer::offer.photos'))
                // Le titre de l'étape « Photos » suffit (décision 2026-11-19-nuit-ordre-photos-libelle).
                ->hideLabel()
                ->help(__('offer::offer.photos_help'))
                ->accept(OfferPhotoService::ACCEPTED_MIME_TYPES)
                ->maxSize(OfferPhotoService::MAX_UPLOAD_KB)
                ->maxFiles(OfferPhotoService::MAX_FILES)
                ->files(app(OfferPhotoService::class)->current($offer))
                ->required(),
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

    /**
     * Titres d'étape centrés (décision 2026-11-19-alignement-titre-formulaire).
     */
    public function titleAlign(): string
    {
        return 'center';
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
}
