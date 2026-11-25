<?php

namespace Modules\Offer\Domains;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Modules\Form\Support\Field;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

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
     * Type de location, type de logement et ville : « Location meublée · Studio · Douala » (décision
     * 2026-11-21-reservations-ville-ligne-du-type).
     */
    public function itemTypeLine(Offer $offer, string $locale): ?string
    {
        $type = (string) $offer->detail('property_type');
        $rental = RentalTypeEnum::of($offer);

        if (! in_array($type, self::PROPERTY_TYPES, true) && $rental === null) {
            return null;
        }

        return implode(' · ', array_filter([
            $rental === null ? null : __('offer::rental.rental_types.'.$rental->value, [], $locale),
            in_array($type, self::PROPERTY_TYPES, true) ? __('offer::rental.property_types.'.$type, [], $locale) : null,
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
     * Les anciens équipements (décision 2026-11-19-contrat-multiselect-intervalle-equipements), devenus des cases de la
     * catégorie « Équipements » (décision 2026-11-25-equipements-en-categories) ; leurs libellés sont dans `offer::rental.amenities`.
     */
    public const AMENITIES = ['wifi', 'parking', 'air_conditioning', 'hot_water', 'water_tank', 'security', 'equipped_kitchen', 'washing_machine', 'iron', 'tv', 'balcony', 'pool'];

    /**
     * Les cases de départ, sous deux catégories racines au même niveau, « Règles » et « Équipements » (décision
     * 2026-11-25-cases-par-categories) : les clés de leurs libellés ; leurs slugs sont tirés des noms (décision
     * 2026-11-25-categories-slug-traduit). Les anciens équipements en tête des « Équipements » (décision 2026-11-25-equipements-en-categories).
     */
    public const FLAG_TREE = [
        'rules' => ['pets', 'smoking', 'parties'],
        // Sans « Forage » : « Réserve d'eau / forage » le couvre (décision 2026-11-25-forage-en-double).
        'features' => [...self::AMENITIES, 'cameras', 'generator'],
    ];

    /** La clé de traduction du nom d'une case de départ. */
    public static function flagLabelKey(string $root, string $key): string
    {
        return match (true) {
            $root === 'rules' => 'offer::rental.rules.'.$key,
            in_array($key, self::AMENITIES, true) => 'offer::rental.amenities.'.$key,
            default => 'offer::rental.'.$key,
        };
    }

    /**
     * Les ids des cases de départ `$keys` d'un groupe (`rule_flags`, `feature_flags`), retrouvées par leur nom dans la
     * langue par défaut ; une case absente est ignorée. Pour les données de démonstration.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function flagIds(string $field, array $keys): array
    {
        $root = $field === 'rule_flags' ? 'rules' : 'features';
        $byName = array_flip(OfferCategory::childrenOf($this->flagGroups()[$field]));

        return array_values(array_filter(array_map(fn (string $key): ?string => isset($byName[$name = (string) __(self::flagLabelKey($root, $key), [], Locales::default())]) ? (string) $byName[$name] : null, $keys)));
    }

    /** Les « Équipements » sur la fiche : une ligne, leurs noms à la suite, comme avant (décision 2026-11-25-equipements-en-categories). */
    public function flagsAsList(): array
    {
        return ['feature_flags'];
    }

    /** Documents qu'un propriétaire peut demander (décision 2026-11-19-contrat-regles-informations). */
    /** Le nom d'un filtre de règle : `rule_` suivi de l'id de sa case. */
    public const RULE_FILTER = 'rule_';

    public const DOCUMENTS = ['id_card', 'passport', 'residence_permit', 'proof_of_income', 'guarantor', 'deposit'];

    public function flagGroups(): array
    {
        // Les racines par leur slug dans la langue par défaut (« regles », « equipements »).
        return [
            'rule_flags' => Str::slug(__('offer::rental.flag_roots.rules', [], Locales::default())),
            'feature_flags' => Str::slug(__('offer::rental.flag_roots.features', [], Locales::default())),
        ];
    }

    /**
     * « Règles » et « Équipements », deux racines au même niveau, et leurs cases (décision 2026-11-25-cases-par-categories) ; le seeder les crée si
     * elles manquent, le back-office les gère ensuite.
     */
    public function categories(): array
    {
        $names = fn (string $key): array => collect(Locales::values())->mapWithKeys(fn (string $locale): array => [$locale => (string) __($key, [], $locale)])->all();
        $label = fn (string $root, string $key): string => self::flagLabelKey($root, $key);

        // Les slugs sont tirés des noms, dans chaque langue ; le seeder retrouve une catégorie par son nom.
        return collect(self::FLAG_TREE)->map(fn (array $children, string $root): array => [
            'name' => $names('offer::rental.flag_roots.'.$root),
            'children' => array_map(fn (string $key): array => ['name' => $names($label($root, $key))], $children),
        ])->values()->all();
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
            // Facultatif (décision 2026-11-21-salles-de-bain).
            Field::number('bathrooms')
                ->label(__('offer::rental.bathrooms'))
                ->rules(['integer', 'min:0', 'max:20']),
            // Les cases de « Équipements » : wifi, parking… caméras, forage, groupe électrogène (décision 2026-11-25-equipements-en-categories).
            $this->flagField('feature_flags'),
            // Pas pour un meublé, qui se réserve par dates (décision 2026-11-20-disponibilite-sans-meuble).
            Field::date('available_from')
                ->label(__('offer::rental.available_from'))
                ->visibleWhen('rental_type', RentalTypeEnum::unfurnishedValues()),
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
            // La caution d'un meublé : un montant, pas un nombre de mois (décision 2026-11-20-caution-meuble).
            Field::number('deposit')
                ->label(__('offer::rental.deposit'))
                ->money()
                ->visibleWhen('rental_type', RentalTypeEnum::FURNISHED->value)
                ->rules(['integer', 'min:0', 'max:100000000']),
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
            // Les cases de « Règles » : animaux, fumeurs, fêtes (décision 2026-11-25-cases-par-categories).
            $this->flagField('rule_flags'),
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
        return ['check_in_from', 'check_out_until', 'rule_flags', 'documents'];
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
     * Les montants vides ou nuls (charges, caution, avance) ne s'affichent pas ; une location non
     * meublée affiche son loyer mensuel après les charges, « (sans charges) » s'il y en a
     * (décision 2026-11-20-fiche-loyer-caution) ; un meublé, son prix par jour avant sa caution
     * (décision 2026-11-20-fiche-prix-par-jour).
     */
    public function adjustFacts(Offer $offer, array $facts, string $locale): array
    {
        // Le type de logement et le type de location sont dans le titre composé, sous le titre : plus dans les
        // caractéristiques (décision 2026-11-22-fiche-sections-ordre).
        unset($facts['property_type'], $facts['rental_type']);

        // La caution d'un meublé, elle, s'affiche toujours, 0 sans montant (décision 2026-11-20-caution-meuble-toujours).
        foreach (['charges', 'deposit_months', 'advance_months'] as $name) {
            if (! is_numeric($value = $offer->detail($name)) || (float) $value <= 0) {
                unset($facts[$name]);
            }
        }

        if ($offer->price === null) {
            return $facts;
        }

        $money = Number::currency((float) $offer->price, (string) ($offer->currency ?? $offer->country?->currency), $locale);
        $keys = array_keys($facts);
        // La place du premier de ces noms parmi les caractéristiques, `null` si aucun.
        $position = function (string ...$names) use ($keys): ?int {
            foreach ($names as $name) {
                if (($index = array_search($name, $keys, true)) !== false) {
                    return $index;
                }
            }

            return null;
        };

        if (RentalTypeEnum::of($offer)?->isFurnished()) {
            // Un meublé : « Prix par jour » puis sa caution, toujours, à la fin (décisions
            // 2026-11-20-fiche-prix-par-jour et 2026-11-20-caution-meuble-toujours).
            $deposit = is_numeric($value = $offer->detail('deposit')) ? (float) $value : 0.0;
            unset($facts['deposit']);

            return [
                ...$facts,
                'rent' => ['label' => (string) __('offer::booking.lines.price_per', ['unit' => (string) $this->priceUnit($offer)]), 'value' => $money],
                'deposit' => ['label' => (string) __('offer::rental.deposit'), 'value' => Number::currency($deposit, (string) ($offer->currency ?? $offer->country?->currency), $locale)],
            ];
        }

        $rent = ['label' => (string) __(isset($facts['charges']) ? 'offer::booking.lines.rent_net' : 'offer::booking.lines.rent'), 'value' => $money];
        // Après les charges, sinon avant la caution et l'avance, sinon à la fin.
        $at = isset($facts['charges']) ? array_search('charges', $keys, true) + 1 : ($position('deposit_months', 'advance_months') ?? count($keys));

        return [...array_slice($facts, 0, $at, true), 'rent' => $rent, ...array_slice($facts, $at, null, true)];
    }

    /**
     * Type, meublé, catégorie en tête de la fiche (décisions 2026-11-19-fiche-offre-ordre
     * et 2026-11-19-fiche-sans-ville).
     */
    public function leadingFacts(): array
    {
        return ['category_id'];
    }

    public function formSteps(): array
    {
        return [
            // « Présentation » (décision 2026-11-19-etape-1-presentation).
            ['title' => __('offer::rental.steps.presentation'), 'fields' => ['property_type', 'rental_type', 'category_id', 'title', 'description']],
            ['title' => __('offer::rental.steps.features'), 'fields' => ['surface', 'rooms', 'bedrooms', 'bathrooms', 'feature_flags']],
            ['title' => __('offer::rental.steps.price'), 'fields' => ['price', 'charges', 'deposit_months', 'advance_months', 'deposit', 'available_from', 'status']],
            // « Règles » (décisions 2026-11-19-contrat-regles-informations et 2026-11-19-etape-informations).
            ['title' => __('offer::rental.steps.rules'), 'fields' => $this->ruleAttributes()],
            // Les blocs d'informations, à part (décision 2026-11-19-etape-informations).
            ['title' => __('offer::rental.steps.informations'), 'fields' => ['informations']],
            ['title' => __('offer::rental.steps.place'), 'fields' => ['street', 'zip', 'region', 'city', 'indication', 'show_address', 'phone', 'email']],
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
            // Au moins tant de salles de bain, comme les chambres (décision 2026-11-21-salles-de-bain).
            Field::select('bathrooms')
                ->label(__('offer::rental.filters.bathrooms'))
                ->options(collect([1, 2, 3])->mapWithKeys(fn (int $count): array => [(string) $count => $count.'+'])->all()),
            // Pas pour la location meublée, qui se réserve par dates : visible sans type de location choisi ou
            // pour un non meublé (décision 2026-11-22-filtre-libre-des-sans-meuble).
            Field::date('available_from')
                ->label(__('offer::rental.filters.available_from'))
                ->help(__('offer::rental.filters.available_from_help'))
                ->visibleWhen('rental_type', ['', ...RentalTypeEnum::unfurnishedValues()]),
            // Les « Équipements » cherchés, par id de case (décision 2026-11-25-equipements-en-categories).
            Field::multiselect('feature_flags')
                ->label(__('offer::rental.amenities_label'))
                ->help(__('offer::rental.filters.amenities_help'))
                ->optionsUsing(fn (): array => OfferCategory::childrenOf($this->flagGroups()['feature_flags'])),
            // Une règle Oui / Non par case de « Règles », comme « Meublé » (décision 2026-11-19-filtres-regles ; décision 2026-11-25-categories-slug-traduit) :
            // une case ajoutée au back-office a son filtre.
            ...array_map(fn (string $id, string $name): Field => Field::segmented(self::RULE_FILTER.$id)
                ->label($name)
                ->options(['any' => __('offer::rental.filters.any'), 'yes' => __('offer::rental.rules.yes'), 'no' => __('offer::rental.rules.no')])
                ->default('any'), array_keys($rules = OfferCategory::childrenOf($this->flagGroups()['rule_flags'])), $rules),
        ];
    }

    public function offerTypeFilters(): array
    {
        return [
            Field::select('property_type')
                ->label(__('offer::rental.property_type'))
                ->options(collect(self::PROPERTY_TYPES)->mapWithKeys(fn (string $type): array => [$type => __('offer::rental.property_types.'.$type)])->all()),
            Field::select('rental_type')
                ->label(__('offer::rental.rental_type'))
                ->options(RentalTypeEnum::options()),
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
        if (isset($filters['bathrooms'])) {
            $query->where('details->bathrooms', '>=', (int) $filters['bathrooms']);
        }
        // Libre à cette date : disponible avant ou ce jour-là, ou sans date (libre tout de suite) ;
        // un meublé n'a pas de date de disponibilité (décision 2026-11-20-disponibilite-sans-meuble).
        if (isset($filters['available_from'])) {
            $query->where(fn (Builder $dates) => $dates
                ->where('details->rental_type', RentalTypeEnum::FURNISHED->value)
                ->orWhereNull('details->available_from')
                ->orWhere('details->available_from', '<=', (string) $filters['available_from']));
        }
        // Tous les équipements choisis, par id de case (décision 2026-11-25-equipements-en-categories).
        foreach ((array) ($filters['feature_flags'] ?? []) as $id) {
            $query->whereJsonContains('details->feature_flags', (string) $id);
        }
        // Une règle : « Oui », sa case cochée ; « Non », pas cochée (décision 2026-11-25-cases-par-categories, remplace « non renseignée ne répond ni oui ni
        // non » de la décision 2026-11-19-filtres-regles).
        // Par l'id de la case dans `details.rule_flags` (décision 2026-11-25-categories-slug-traduit).
        foreach (array_keys(OfferCategory::childrenOf($this->flagGroups()['rule_flags'])) as $id) {
            $answer = $filters[self::RULE_FILTER.$id] ?? 'any';
            if ($answer === 'yes') {
                $query->whereJsonContains('details->rule_flags', (string) $id);
            } elseif ($answer === 'no') {
                $query->where(fn (Builder $unchecked) => $unchecked->whereNull('details->rule_flags')->orWhereJsonDoesntContain('details->rule_flags', (string) $id));
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
     * Les charges, la caution et l'avance de loyer, après le loyer, dans l'onglet « Prix »
     * (décisions 2026-11-19-onglet-prix et 2026-11-23-caution-avance-onglet-prix).
     */
    public function priceAttributes(): array
    {
        return ['charges', 'deposit_months', 'advance_months', 'deposit'];
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
