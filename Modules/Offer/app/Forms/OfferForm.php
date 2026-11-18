<?php

namespace Modules\Offer\Forms;

use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
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
        $this->offer = Offer::query()->where('user_id', $this->user?->getAuthIdentifier())->whereKey($key)->first() ?? abort(404);

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
            ! $domain->hasPrice() ? null : Field::number('price')
                ->label($domain->priceLabel().' ('.$domain->currency().')')
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
            ...array_map(fn (Field $field): Field => $field->default($offer?->detailIn($field->name, $this->locale())), $domain->attributes()),
        ]));
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
     * Les adresses du compte, sans doublon : id de l'adresse => « Rue du Lac 4, 1200 Genève ».
     *
     * @return array<string, string>
     */
    private function addressOptions(): array
    {
        $user = $this->user instanceof User ? $this->user : null;

        return $user === null ? [] : $user->addresses()->with('address')->get()
            ->unique('address_id')
            ->mapWithKeys(fn (UserAddress $entry): array => [(string) $entry->address_id => $entry->address->street.', '.$entry->address->zip.' '.$entry->address->city])
            ->all();
    }
}
