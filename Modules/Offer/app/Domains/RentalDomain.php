<?php

namespace Modules\Offer\Domains;

use Illuminate\Database\Eloquent\Builder;
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
     * Équipements proposés (décision 2026-11-19-contrat-multiselect-intervalle-equipements) ;
     * leurs libellés sont dans `offer::rental.amenities`.
     */
    public const AMENITIES = ['wifi', 'parking', 'air_conditioning', 'hot_water', 'generator', 'water_tank', 'security', 'equipped_kitchen', 'washing_machine', 'iron', 'tv', 'balcony', 'pool'];

    /**
     * @return array<string, string>
     */
    private function amenityOptions(): array
    {
        return collect(self::AMENITIES)->mapWithKeys(fn (string $amenity): array => [$amenity => __('offer::rental.amenities.'.$amenity)])->all();
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
            // Facultatives (décision 2026-11-19-charges-meuble-surface-facultative).
            Field::number('surface')
                ->label(__('offer::rental.surface'))
                ->suffix('m²')
                ->rules(['min:1', 'max:10000']),
            Field::number('rooms')
                ->label(__('offer::rental.rooms'))
                ->help(__('offer::rental.rooms_help'))
                ->rules(['min:1', 'max:50']),
            Field::number('bedrooms')
                ->label(__('offer::rental.bedrooms'))
                ->rules(['integer', 'min:0', 'max:50']),
            Field::date('available_from')
                ->label(__('offer::rental.available_from')),
            Field::multiselect('amenities')
                ->label(__('offer::rental.amenities_label'))
                ->options($this->amenityOptions()),
            Field::number('charges')
                ->label(__('offer::rental.charges'))
                ->help(__('offer::rental.charges_help'))
                // Un montant mensuel : « FCFA / mois », la devise venant de qui l'affiche.
                ->money(__('offer::rental.price_units.month'))
                // Un meublé se loue charges comprises : pas de charges.
                ->visibleWhen('furnished', 'false')
                ->rules(['min:0', 'max:100000']),
        ];
    }

    /**
     * Cinq étapes dans l'app (décisions 2026-11-19-etapes-formulaire-ordre-final et 2026-11-19-contrat-champ-upload-photos) :
     * la présentation (type, meublé, catégorie, titre, description), les
     * caractéristiques, le prix et la disponibilité (avec le statut), l'adresse, puis les photos.
     */
    /**
     * Type, meublé, ville, catégorie en tête de la fiche (décision 2026-11-19-fiche-offre-ordre).
     */
    public function leadingFacts(): array
    {
        return ['property_type', 'furnished', 'city', 'category_id'];
    }

    public function formSteps(): array
    {
        return [
            // « Présentation » (décision 2026-11-19-etape-1-presentation).
            ['title' => __('offer::rental.steps.presentation'), 'fields' => ['property_type', 'furnished', 'category_id', 'title', 'description']],
            ['title' => __('offer::rental.steps.features'), 'fields' => ['surface', 'rooms', 'bedrooms', 'amenities']],
            ['title' => __('offer::rental.steps.price'), 'fields' => ['price', 'charges', 'available_from', 'status']],
            ['title' => __('offer::rental.steps.place'), 'fields' => ['street', 'zip', 'city', 'indication']],
            // Les photos en dernier (décision 2026-11-19-contrat-champ-upload-photos).
            ['title' => __('offer::rental.steps.photos'), 'fields' => ['photos']],
        ];
    }

    /**
     * Type (plusieurs), meublé, chambres au moins, disponible dès le (décision
     * 2026-11-19-contrat-filtres-vitrine).
     */
    public function filters(): array
    {
        return [
            Field::multiselect('property_type')
                ->label(__('offer::rental.property_type'))
                ->options(collect(self::PROPERTY_TYPES)->mapWithKeys(fn (string $type): array => [$type => __('offer::rental.property_types.'.$type)])->all()),
            Field::segmented('furnished')
                ->label(__('offer::rental.furnished'))
                ->options(['any' => __('offer::rental.filters.any'), 'true' => __('offer::rental.filters.furnished'), 'false' => __('offer::rental.filters.unfurnished')])
                ->default('any'),
            Field::select('bedrooms')
                ->label(__('offer::rental.filters.bedrooms'))
                ->options(collect([1, 2, 3, 4])->mapWithKeys(fn (int $count): array => [(string) $count => $count.'+'])->all()),
            Field::date('available_from')
                ->label(__('offer::rental.filters.available_from'))
                ->help(__('offer::rental.filters.available_from_help')),
            Field::multiselect('amenities')
                ->label(__('offer::rental.amenities_label'))
                ->help(__('offer::rental.filters.amenities_help'))
                ->options($this->amenityOptions()),
        ];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['property_type'])) {
            $query->whereIn('details->property_type', (array) $filters['property_type']);
        }
        // « Tous » ne filtre rien.
        if (in_array($filters['furnished'] ?? 'any', ['true', 'false'], true)) {
            $query->where('details->furnished', $filters['furnished'] === 'true');
        }
        if (isset($filters['bedrooms'])) {
            $query->where('details->bedrooms', '>=', (int) $filters['bedrooms']);
        }
        // Libre à cette date : disponible avant ou ce jour-là, ou sans date (libre tout de suite).
        if (isset($filters['available_from'])) {
            $query->where(fn (Builder $dates) => $dates
                ->whereNull('details->available_from')
                ->orWhere('details->available_from', '<=', (string) $filters['available_from']));
        }
        // Tous les équipements choisis.
        foreach ((array) ($filters['amenities'] ?? []) as $amenity) {
            $query->whereJsonContains('details->amenities', (string) $amenity);
        }
    }

    /**
     * Back-office : type et meublé dans « Annonce » ; le loyer avant les charges
     * (décision 2026-11-19-backoffice-loyer-type-annonce).
     */
    public function listingAttributes(): array
    {
        return ['property_type', 'furnished'];
    }

    public function priceBeforeAttribute(): ?string
    {
        return 'charges';
    }

    public function accessAttributes(): array
    {
        return ['available_from'];
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
