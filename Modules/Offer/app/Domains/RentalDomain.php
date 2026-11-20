<?php

namespace Modules\Offer\Domains;

use Illuminate\Database\Eloquent\Builder;
use Modules\Form\Support\Field;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Enums\RentalTypeEnum;
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

    public function requestLabel(): string
    {
        return __('offer::rental.request');
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
            RentalTypeEnum::of($offer)?->isFurnished() ? __('offer::rental.furnished_words.'.$type) : null,
            $offer->address?->city,
        ]));
    }

    /**
     * Meublé : au jour ; sinon au mois (décisions 2026-11-18-prix-par-nuit-ou-mois
     * et 2026-11-18-prix-meuble-par-jour).
     */
    public function priceUnit(Offer $offer): ?string
    {
        return __('offer::rental.price_units.'.(RentalTypeEnum::of($offer)?->isFurnished() ? 'day' : 'month'));
    }

    /**
     * Pendant la saisie : « / jour » pour une location meublée, « / mois » sinon.
     */
    public function priceUnitWhen(): ?array
    {
        return [
            'field' => 'rental_type',
            'units' => [
                RentalTypeEnum::FURNISHED->value => __('offer::rental.price_units.day'),
                RentalTypeEnum::LONG_TERM->value => __('offer::rental.price_units.month'),
                RentalTypeEnum::SHARED->value => __('offer::rental.price_units.month'),
            ],
            'default' => __('offer::rental.price_units.month'),
        ];
    }

    /**
     * Équipements proposés (décision 2026-11-19-contrat-multiselect-intervalle-equipements) ;
     * leurs libellés sont dans `offer::rental.amenities`.
     */
    public const AMENITIES = ['wifi', 'parking', 'air_conditioning', 'hot_water', 'generator', 'water_tank', 'security', 'equipped_kitchen', 'washing_machine', 'iron', 'tv', 'balcony', 'pool'];

    /** Documents qu'un propriétaire peut demander (décision 2026-11-19-contrat-regles-informations). */
    /** Les règles qui se répondent par Oui / Non : saisies, affichées et filtrées pareil. */
    public const YES_NO_RULES = ['pets', 'smoking', 'parties'];

    public const DOCUMENTS = ['id_card', 'passport', 'residence_permit', 'proof_of_income', 'guarantor', 'deposit'];

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
            // Le type de location, obligatoire : il remplace « Meublé » (décision 2026-11-20-contrat-type-de-location).
            Field::select('rental_type')
                ->label(__('offer::rental.rental_type'))
                ->options(RentalTypeEnum::options())
                ->required(),
            // Facultatives (décision 2026-11-19-charges-meuble-surface-facultative).
            Field::number('surface')
                ->label(__('offer::rental.surface'))
                ->suffix('m²')
                ->rules(['min:1', 'max:10000']),
            Field::number('rooms')
                ->label(__('offer::rental.rooms'))
                ->help(__('offer::rental.rooms_help'))
                ->rules(['min:1', 'max:50']),
            // Obligatoire (décision 2026-11-20-chambres-obligatoires) : 0 pour un studio.
            Field::number('bedrooms')
                ->label(__('offer::rental.bedrooms'))
                ->required()
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
                ->visibleWhen('rental_type', RentalTypeEnum::unfurnishedValues())
                ->rules(['min:0', 'max:100000']),
            // La caution et l'avance de loyer, en mois, pour une location non meublée, facultatives
            // (décision 2026-11-20-caution-avance-loyer).
            Field::number('deposit_months')
                ->label(__('offer::rental.deposit_months'))
                ->suffix(__('offer::rental.months'))
                ->visibleWhen('rental_type', RentalTypeEnum::unfurnishedValues())
                ->rules(['integer', 'min:0', 'max:24']),
            Field::number('advance_months')
                ->label(__('offer::rental.advance_months'))
                ->suffix(__('offer::rental.months'))
                ->visibleWhen('rental_type', RentalTypeEnum::unfurnishedValues())
                ->rules(['integer', 'min:0', 'max:24']),
            // Règles, toutes facultatives (décision 2026-11-19-contrat-regles-informations). Pas d'âge
            // minimum propre à l'offre : celui de l'application vaut pour toutes (décision
            // 2026-11-19-age-minimum-de-l-app).
            // Les heures d'arrivée et de départ d'un meublé, de retour (décision 2026-11-20-heures-arrivee-depart) :
            // elles datent l'arrivée et le départ d'une demande.
            Field::select('check_in_from')
                ->label(__('offer::rental.rules.check_in_from'))
                ->options($this->hourOptions())
                ->visibleWhen('rental_type', RentalTypeEnum::FURNISHED->value),
            Field::select('check_out_until')
                ->label(__('offer::rental.rules.check_out_until'))
                ->options($this->hourOptions())
                ->visibleWhen('rental_type', RentalTypeEnum::FURNISHED->value),
            // Oui / Non plutôt qu'un interrupteur : « non renseigné » n'est pas « non ».
            ...array_map(fn (string $rule): Field => Field::select($rule)
                ->label(__('offer::rental.rules.'.$rule))
                ->options(['yes' => __('offer::rental.rules.yes'), 'no' => __('offer::rental.rules.no')]), self::YES_NO_RULES),
            // Les documents en dernier (décision 2026-11-19-documents-en-dernier).
            Field::multiselect('documents')
                ->label(__('offer::rental.rules.documents'))
                ->options(collect(self::DOCUMENTS)->mapWithKeys(fn (string $document): array => [$document => __('offer::rental.rules.document_options.'.$document)])->all()),
        ];
    }

    /**
     * Les règles, à part sur la fiche et dans l'onglet « Règles » du back-office.
     */
    public function ruleAttributes(): array
    {
        return ['check_in_from', 'check_out_until', 'pets', 'smoking', 'parties', 'documents'];
    }

    /**
     * « 06:00 », « 06:30 »… « 23:00 » : les heures d'arrivée et de départ proposées.
     *
     * @return array<string, string>
     */
    private function hourOptions(): array
    {
        $hours = [];
        for ($minutes = 6 * 60; $minutes <= 23 * 60; $minutes += 30) {
            $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $hours[$time] = $time;
        }

        return $hours;
    }

    /**
     * Cinq étapes dans l'app (décisions 2026-11-19-etapes-formulaire-ordre-final et 2026-11-19-contrat-champ-upload-photos) :
     * la présentation (type, meublé, catégorie, titre, description), les
     * caractéristiques, le prix et la disponibilité (avec le statut), l'adresse, puis les photos.
     */
    /**
     * Type, meublé, catégorie en tête de la fiche (décisions 2026-11-19-fiche-offre-ordre
     * et 2026-11-19-fiche-sans-ville).
     */
    public function leadingFacts(): array
    {
        return ['property_type', 'rental_type', 'category_id'];
    }

    public function formSteps(): array
    {
        return [
            // « Présentation » (décision 2026-11-19-etape-1-presentation).
            ['title' => __('offer::rental.steps.presentation'), 'fields' => ['property_type', 'rental_type', 'category_id', 'title', 'description']],
            ['title' => __('offer::rental.steps.features'), 'fields' => ['surface', 'rooms', 'bedrooms', 'amenities']],
            ['title' => __('offer::rental.steps.price'), 'fields' => ['price', 'charges', 'deposit_months', 'advance_months', 'available_from', 'status']],
            // « Règles » (décisions 2026-11-19-contrat-regles-informations et 2026-11-19-etape-informations).
            ['title' => __('offer::rental.steps.rules'), 'fields' => $this->ruleAttributes()],
            // Les blocs d'informations, à part (décision 2026-11-19-etape-informations).
            ['title' => __('offer::rental.steps.informations'), 'fields' => ['informations']],
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
            Field::select('rental_type')
                ->label(__('offer::rental.rental_type'))
                ->options(RentalTypeEnum::options()),
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
            // Les règles Oui / Non, comme « Meublé » (décision 2026-11-19-filtres-regles).
            ...array_map(fn (string $rule): Field => Field::segmented($rule)
                ->label(__('offer::rental.rules.'.$rule))
                ->options(['any' => __('offer::rental.filters.any'), 'yes' => __('offer::rental.rules.yes'), 'no' => __('offer::rental.rules.no')])
                ->default('any'), self::YES_NO_RULES),
        ];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['property_type'])) {
            $query->whereIn('details->property_type', (array) $filters['property_type']);
        }
        // « Tous » ne filtre rien.
        if (is_string($filters['rental_type'] ?? null) && RentalTypeEnum::tryFrom($filters['rental_type']) !== null) {
            $query->where('details->rental_type', $filters['rental_type']);
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
        // Une règle Oui / Non : la réponse du propriétaire ; une règle non renseignée ne
        // répond ni oui ni non (décision 2026-11-19-filtres-regles).
        foreach (self::YES_NO_RULES as $rule) {
            if (in_array($filters[$rule] ?? 'any', ['yes', 'no'], true)) {
                $query->where('details->'.$rule, $filters[$rule]);
            }
        }
    }

    /**
     * Back-office : type et meublé dans « Général » (décision 2026-11-19-backoffice-loyer-type-annonce).
     */
    public function listingAttributes(): array
    {
        return ['property_type', 'rental_type'];
    }

    /**
     * Une location meublée se réserve par dates ; une location longue durée ou une
     * colocation se visite (décisions 2026-11-19-contrat-reservations et 2026-11-20-contrat-type-de-location).
     */
    public function bookingType(Offer $offer): ?BookingTypeEnum
    {
        return RentalTypeEnum::of($offer)?->isFurnished() ? BookingTypeEnum::STAY : BookingTypeEnum::VISIT;
    }

    /**
     * Les charges, après le loyer, dans l'onglet « Prix » (décision 2026-11-19-onglet-prix).
     */
    public function priceAttributes(): array
    {
        return ['charges'];
    }

    public function accessAttributes(): array
    {
        return ['available_from'];
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
