<?php

namespace Modules\Offer\Domains;

use Modules\Form\Support\Field;

/**
 * Un domaine du projet (location, boutique, rencontre…) : ce qui distingue ses
 * offres, déclaré en code et choisi par `config('offer.domain')` (décision
 * 2026-11-18-offres-et-domaines). Le socle (modèle, API, formulaire et liste de
 * l'app, back-office) lit le domaine actif et ne change pas d'un domaine à l'autre.
 */
abstract class Domain
{
    /** Champs communs à toutes les offres : un attribut ne peut pas porter ces noms. */
    public const COMMON_FIELDS = ['title', 'description', 'category_id', 'price', 'currency', 'address_id', 'status'];

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
     * Attributs propres au domaine, rangés dans la colonne `attributes` : leur
     * schéma sert au formulaire de l'app, à la validation et au back-office.
     *
     * @return list<Field>
     */
    abstract public function attributes(): array;

    /**
     * Catégories de départ, créées par le seeder si elles manquent.
     *
     * @return list<array{slug: string, name: array<string, string>}>
     */
    public function categories(): array
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

    /** Devise des prix (ISO 4217). */
    public function currency(): string
    {
        return 'CHF';
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
}
