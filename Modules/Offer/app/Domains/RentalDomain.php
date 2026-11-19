<?php

namespace Modules\Offer\Domains;

use Modules\Form\Support\Field;
use Modules\Offer\Models\Offer;

/**
 * Location de logements, meublés ou non (décision 2026-11-18-offres-et-domaines) :
 * le prix est le loyer, par jour pour un meublé, par mois sinon (décision
 * 2026-11-18-prix-par-nuit-ou-mois) ; l'adresse est obligatoire.
 */
class RentalDomain extends Domain
{
    public const PROPERTY_TYPES = ['apartment', 'house', 'studio', 'room'];

    public static function key(): string
    {
        return 'rental';
    }

    public function label(): string
    {
        return __('offer::rental.singular');
    }

    public function pluralLabel(): string
    {
        return __('offer::rental.plural');
    }

    public function mineLabel(): string
    {
        return __('offer::rental.mine');
    }

    public function addLabel(): string
    {
        return __('offer::rental.add');
    }

    public function priceLabel(): string
    {
        return __('offer::rental.price');
    }

    /**
     * « Studio · meublé · Douala » : le type, « meublé » accordé s'il l'est, la
     * ville s'il y en a une, séparés par « · », comme les détails (décisions
     * 2026-11-18-titre-liste-logement et 2026-11-19-titre-separateur-point).
     */
    public function itemTitle(Offer $offer): ?string
    {
        $type = (string) $offer->detail('property_type');

        if (! in_array($type, self::PROPERTY_TYPES, true)) {
            return null;
        }

        return implode(' · ', array_filter([
            __('offer::rental.property_types.'.$type),
            $offer->detail('furnished') ? __('offer::rental.furnished_words.'.$type) : null,
            $offer->address?->city,
        ]));
    }

    /**
     * Meublé : au jour ; sinon au mois (décisions 2026-11-18-prix-par-nuit-ou-mois
     * et 2026-11-18-prix-meuble-par-jour).
     */
    public function priceUnit(Offer $offer): ?string
    {
        return __('offer::rental.price_units.'.($offer->detail('furnished') ? 'day' : 'month'));
    }

    /**
     * Pendant la saisie : « / jour » si « Meublé » est coché, « / mois » sinon.
     */
    public function priceUnitWhen(): ?array
    {
        return [
            'field' => 'furnished',
            'units' => ['true' => __('offer::rental.price_units.day'), 'false' => __('offer::rental.price_units.month')],
            'default' => __('offer::rental.price_units.month'),
        ];
    }

    /**
     * Plus d'une chambre : leur nombre, avant le prix (« 3 chambres »).
     */
    public function itemDetails(Offer $offer): array
    {
        $bedrooms = (int) $offer->detail('bedrooms');

        return $bedrooms > 1 ? [trans_choice('offer::rental.bedrooms_count', $bedrooms, ['count' => $bedrooms])] : [];
    }

    public function attributes(): array
    {
        return [
            Field::select('property_type')
                ->label(__('offer::rental.property_type'))
                ->options(collect(self::PROPERTY_TYPES)->mapWithKeys(fn (string $type): array => [$type => __('offer::rental.property_types.'.$type)])->all())
                ->required(),
            Field::switch('furnished')
                ->label(__('offer::rental.furnished')),
            Field::number('surface')
                ->label(__('offer::rental.surface'))
                ->suffix('m²')
                ->required()
                ->rules(['min:1', 'max:10000']),
            Field::number('rooms')
                ->label(__('offer::rental.rooms'))
                ->help(__('offer::rental.rooms_help'))
                ->required()
                ->rules(['min:1', 'max:50']),
            Field::number('bedrooms')
                ->label(__('offer::rental.bedrooms'))
                ->rules(['integer', 'min:0', 'max:50']),
            Field::date('available_from')
                ->label(__('offer::rental.available_from')),
            Field::number('charges')
                ->label(__('offer::rental.charges'))
                ->help(__('offer::rental.charges_help'))
                // Un montant mensuel : « FCFA / mois », la devise venant de qui l'affiche.
                ->money(__('offer::rental.price_units.month'))
                ->rules(['min:0', 'max:100000']),
        ];
    }

    /**
     * Quatre étapes dans l'app : le logement, le prix et la disponibilité,
     * l'adresse et la catégorie, puis l'annonce.
     */
    public function formSteps(): array
    {
        return [
            ['title' => __('offer::rental.steps.home'), 'fields' => ['property_type', 'furnished', 'surface', 'rooms', 'bedrooms']],
            ['title' => __('offer::rental.steps.price'), 'fields' => ['price', 'charges', 'available_from']],
            ['title' => __('offer::rental.steps.place'), 'fields' => ['address_id', 'category_id']],
            ['title' => __('offer::rental.steps.listing'), 'fields' => ['title', 'description', 'status']],
        ];
    }

    public function categories(): array
    {
        return [
            ['slug' => 'long-term', 'name' => ['fr' => 'Location longue durée', 'en' => 'Long-term rental']],
            ['slug' => 'furnished', 'name' => ['fr' => 'Location meublée', 'en' => 'Furnished rental']],
            ['slug' => 'shared', 'name' => ['fr' => 'Colocation', 'en' => 'Flat share']],
        ];
    }

    public function requiresPrice(): bool
    {
        return true;
    }

    public function hasAddress(): bool
    {
        return true;
    }

    public function requiresAddress(): bool
    {
        return true;
    }
}
