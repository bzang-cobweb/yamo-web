<?php

namespace Modules\Offer\Domains;

use Modules\Form\Support\Field;
use Modules\Offer\Models\Offer;

/**
 * Location de logements, meublés ou non (décision 2026-11-18-offres-et-domaines) :
 * le prix est le loyer, par nuit pour un meublé, par mois sinon (décision
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
     * « Studio meublé - Douala » : le type, « meublé » accordé s'il l'est, la
     * ville s'il y en a une (décision 2026-11-18-titre-liste-logement).
     */
    public function itemTitle(Offer $offer): ?string
    {
        $type = (string) $offer->detail('property_type');

        if (! in_array($type, self::PROPERTY_TYPES, true)) {
            return null;
        }

        $label = __('offer::rental.'.($offer->detail('furnished') ? 'furnished_types' : 'property_types').'.'.$type);
        $city = $offer->address?->city;

        return $city === null || $city === '' ? $label : __('offer::rental.title_in_city', ['type' => $label, 'city' => $city]);
    }

    /**
     * Meublé : à la nuit ; sinon au mois (décision 2026-11-18-prix-par-nuit-ou-mois).
     */
    public function priceUnit(Offer $offer): ?string
    {
        return __('offer::rental.price_units.'.($offer->detail('furnished') ? 'night' : 'month'));
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
                ->rules(['min:0', 'max:100000']),
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
