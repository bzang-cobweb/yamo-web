<?php

namespace Modules\Offer\Domains;

use Modules\Form\Support\Field;

/**
 * Location de logements, meublés ou non (décision 2026-11-18-offres-et-domaines) :
 * le prix est le loyer mensuel, l'adresse est obligatoire.
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
