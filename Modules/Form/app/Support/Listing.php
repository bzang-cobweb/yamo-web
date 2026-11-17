<?php

namespace Modules\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Une liste décrite par le serveur, que l'app affiche sans la connaître d'avance
 * (décision 2026-11-18-adresses-liste-generique) : titre, texte quand elle est
 * vide, items, et bouton de création qui ouvre un formulaire. Chaque item peut
 * s'ouvrir dans un formulaire et se retirer.
 *
 * `List` étant un mot réservé de PHP, la classe s'appelle `Listing`. Chaque liste
 * est déclarée dans le module qui la possède et inscrite au registre
 * (`ListingRegistry::register()`).
 */
abstract class Listing
{
    public function __construct(protected ?Authenticatable $user = null) {}

    /**
     * Nom public de la liste, utilisé dans l'URL (`/api/v1/lists/{name}`).
     */
    abstract public static function name(): string;

    /**
     * Le nom, depuis une instance (sérialisation).
     */
    final public function key(): string
    {
        return static::name();
    }

    /**
     * Titre de l'écran, traduit.
     */
    abstract public function title(): string;

    /**
     * Les items, dans l'ordre d'affichage.
     *
     * @return list<ListItem>
     */
    abstract public function items(): array;

    /**
     * Texte affiché quand la liste est vide, traduit ; aucun par défaut.
     */
    public function emptyText(): ?string
    {
        return null;
    }

    /**
     * Bouton de création : son libellé et le formulaire qu'il ouvre ; aucun par
     * défaut (liste en lecture seule).
     *
     * @return array{label: string, form: string}|null
     */
    public function create(): ?array
    {
        return null;
    }

    /**
     * Qui peut demander la liste. Par défaut : un compte connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }
}
