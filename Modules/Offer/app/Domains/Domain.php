<?php

namespace Modules\Offer\Domains;

use App\Support\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Modules\Form\Support\Field;
use Modules\Offer\Models\Offer;

/**
 * Un domaine du projet (location, boutique, rencontre…) : ce qui distingue ses
 * offres, déclaré en code et choisi par `config('offer.domain')` (décision
 * 2026-11-18-offres-et-domaines). Le socle (modèle, API, formulaire et liste de
 * l'app, back-office) lit le domaine actif et ne change pas d'un domaine à l'autre.
 */
abstract class Domain
{
    /** Champs communs à toutes les offres : un attribut ne peut pas porter ces noms. */
    public const COMMON_FIELDS = ['title', 'description', 'category_id', 'price', 'currency', 'address_id', 'indication', 'informations', 'content_locale', 'street', 'zip', 'city', 'status'];

    /**
     * Clé du domaine, celle de `config('offer.domain')`.
     */
    abstract public static function key(): string;

    /**
     * Une offre, au singulier (« Logement »).
     */
    abstract public function label(): string;

    /**
     * Les offres, au pluriel (« Logements »).
     */
    abstract public function pluralLabel(): string;

    /**
     * Les offres du compte, titre de la liste et du lien du menu (« Mes logements »).
     */
    abstract public function mineLabel(): string;

    /**
     * Libellé du lien de création (« Ajouter un logement »).
     */
    public function addLabel(): string
    {
        return __('offer::offer.list.create');
    }

    /**
     * Libellé du prix (« Loyer mensuel »).
     */
    public function priceLabel(): string
    {
        return __('offer::offer.price');
    }

    /**
     * Attributs propres au domaine, rangés dans la colonne JSON `details` : leur
     * schéma sert au formulaire de l'app, à la validation et au back-office.
     *
     * @return list<Field>
     */
    abstract public function attributes(): array;

    /**
     * Catégories de départ, en arbre (`children`), créées par le seeder si elles manquent.
     *
     * @return list<array{slug: string, name: array<string, string>, children?: list<array<string, mixed>>}>
     */
    public function categories(): array
    {
        return [];
    }

    /**
     * Titre d'une offre dans les listes, construit par le domaine (décision
     * 2026-11-18-titre-liste-logement) ; `null` : le titre saisi.
     */
    public function itemTitle(Offer $offer): ?string
    {
        return null;
    }

    /**
     * Unité du prix d'une offre (« jour », « mois »), affichée après le prix :
     * « 60 000 FCFA / jour » (décision 2026-11-18-prix-par-nuit-ou-mois) ;
     * `null` : aucune.
     */
    public function priceUnit(Offer $offer): ?string
    {
        return null;
    }

    /**
     * L'unité du prix pendant la saisie, quand elle dépend d'un autre champ :
     * `{field, units: valeur => unité, default}` ; `null` : aucune.
     *
     * @return array{field: string, units: array<string, string>, default: string}|null
     */
    public function priceUnitWhen(): ?array
    {
        return null;
    }

    /**
     * Détails d'une offre placés avant le prix dans le sous-titre des listes
     * (« 3 chambres »), dans l'ordre (décision 2026-11-18-unite-prix-chambres).
     *
     * @return list<string>
     */
    public function itemDetails(Offer $offer): array
    {
        return [];
    }

    /**
     * Le prix formaté, suivi de son unité s'il y en a une ; `null` sans prix.
     */
    final public function formattedPrice(Offer $offer, string $locale): ?string
    {
        if ($offer->price === null) {
            return null;
        }

        $price = Number::currency((float) $offer->price, (string) $offer->currency, $locale);
        $unit = $this->priceUnit($offer);

        return $unit === null ? $price : $price.' / '.$unit;
    }

    /**
     * Modules optionnels requis (ex. `Booking`), activés par `project:init`.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        return [];
    }

    /** Le prix est-il demandé ? Obligatoire si `requiresPrice()`. */
    public function hasPrice(): bool
    {
        return true;
    }

    public function requiresPrice(): bool
    {
        return false;
    }

    /** L'offre se situe-t-elle à une adresse du compte ? Obligatoire si `requiresAddress()`. */
    public function hasAddress(): bool
    {
        return false;
    }

    public function requiresAddress(): bool
    {
        return false;
    }

    /** Un seul produit par compte (ex. le profil d'un site de rencontre). */
    public function onePerAccount(): bool
    {
        return false;
    }

    /** Un membre (rôle `member`) publie-t-il ses propres offres depuis l'app ? */
    public function membersCreateOffers(): bool
    {
        return true;
    }

    /**
     * Les caractéristiques en tête de la fiche, dans l'ordre (décision
     * 2026-11-19-fiche-offre-ordre) : noms d'attributs du domaine, `city` (la ville)
     * ou `category_id` (la catégorie). Les autres suivent dans leur ordre habituel.
     * Aucune par défaut.
     *
     * @return list<string>
     */
    public function leadingFacts(): array
    {
        return [];
    }

    /**
     * Les étapes du formulaire de l'app (décision
     * 2026-11-19-contrat-formulaire-etapes-symbole-devise) : titre traduit (ou `null`, sans titre) et noms
     * des champs, attributs du domaine et champs communs (`COMMON_FIELDS`). Aucune
     * par défaut : un formulaire d'un seul tenant.
     *
     * @return list<array{title: string|null, fields: list<string>}>
     */
    public function formSteps(): array
    {
        return [];
    }

    /**
     * Les filtres propres au domaine dans la vitrine (décision
     * 2026-11-19-contrat-filtres-vitrine), en champs ; aucun par défaut.
     *
     * @return list<Field>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Restreint `$query` (des offres) aux valeurs de ces filtres, déjà validées.
     *
     * @param  Builder<Offer>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void {}

    /**
     * Attributs placés dans l'onglet « Annonce » du back-office, avant la catégorie
     * (décision 2026-11-19-backoffice-loyer-type-annonce). Aucun par défaut : tous
     * vont dans « Caractéristiques ».
     *
     * @return list<string>
     */
    public function listingAttributes(): array
    {
        return [];
    }

    /**
     * Les attributs qui accompagnent le prix dans l'onglet « Prix » du back-office,
     * juste après lui (décision 2026-11-19-onglet-prix) ; aucun par défaut.
     *
     * @return list<string>
     */
    public function priceAttributes(): array
    {
        return [];
    }

    /**
     * Attributs qui règlent l'accès à l'offre (« Disponible dès le ») : dans le
     * back-office, ils rejoignent la section « Accès » (décision
     * 2026-11-19-acces-avis-backoffice). Aucun par défaut.
     *
     * @return list<string>
     */
    public function accessAttributes(): array
    {
        return [];
    }

    /**
     * Attributs qui sont des règles du logement (« Arrivée à partir de », « Âge
     * minimum »…), décision 2026-11-19-contrat-regles-informations : la fiche les
     * sert à part (`rules`), le back-office les range dans l'onglet « Règles ».
     * Aucun par défaut.
     *
     * @return list<string>
     */
    public function ruleAttributes(): array
    {
        return [];
    }

    /**
     * Noms des attributs.
     *
     * @return list<string>
     */
    final public function attributeNames(): array
    {
        return array_map(fn (Field $field): string => $field->name, $this->attributes());
    }

    /**
     * Les attributs, tels qu'on les saisit : un montant prend le symbole de la
     * devise `$currency` (code ISO), s'il est connu. Les coordonnées ne sont plus
     * refusées (décision 2026-11-19-modele-proprietaire-payant).
     *
     * @return list<Field>
     */
    final public function inputAttributes(?string $currency = null): array
    {
        $symbol = Currency::symbol($currency);

        return array_map(
            fn (Field $field): Field => $field->inCurrency($symbol),
            $this->attributes(),
        );
    }
}
