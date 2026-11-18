<?php

namespace Modules\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor;
use TypeError;
use UnexpectedValueException;

/**
 * Une liste décrite par le serveur, que l'app affiche sans la connaître d'avance
 * (décision 2026-11-18-adresses-liste-generique) : titre, texte quand elle est
 * vide, items, et bouton de création qui ouvre un formulaire. Chaque item peut
 * s'ouvrir dans un formulaire et se retirer.
 *
 * Les items sont servis par pages, repérées par un curseur (décision
 * 2026-11-18-contrat-liste-infinie-role-admin) : une liste déclare sa requête
 * (`query()`, dans un ordre stable) et la mise en forme d'un enregistrement
 * (`item()`) ; la pagination est faite ici.
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
     * Les enregistrements de la liste, dans l'ordre d'affichage. L'ordre doit
     * finir par une colonne unique (l'`id`) : c'est lui que suit le curseur.
     *
     * @return Builder<covariant Model>|Relation<covariant Model, covariant Model, mixed>
     */
    abstract public function query(): Builder|Relation;

    /**
     * Un enregistrement, mis en forme pour l'app.
     */
    abstract public function item(Model $record): ListItem;

    /**
     * Nombre d'items par page.
     */
    public function perPage(): int
    {
        return 20;
    }

    /**
     * Une page d'items : la première sans curseur, sinon celle qui suit le
     * curseur. Un curseur illisible ou qui ne correspond pas à l'ordre de la
     * liste sert la première page.
     *
     * @return CursorPaginator<int, Model>
     */
    final public function page(?string $cursor = null): CursorPaginator
    {
        // Nom de curseur absent de toute requête : seul `$cursor` compte.
        $name = 'listing_cursor';

        try {
            return $this->query()->cursorPaginate($this->perPage(), ['*'], $name, Cursor::fromEncoded($cursor));
        } catch (UnexpectedValueException|TypeError) {
            return $this->query()->cursorPaginate($this->perPage(), ['*'], $name);
        }
    }

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
