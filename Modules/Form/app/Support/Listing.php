<?php

namespace Modules\Form\Support;

use App\Support\ContainsSearch;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
 * (`item()`) ; la pagination est faite ici. Une liste peut se rendre cherchable
 * (décision 2026-11-18-recherche-dans-la-liste) : `searchPlaceholder()` et
 * `search()`.
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
     * @param  array<string, mixed>  $filters  filtres validés (`validFilters()`)
     * @return CursorPaginator<int, Model>
     */
    final public function page(?string $cursor = null, ?string $search = null, array $filters = []): CursorPaginator
    {
        // Nom de curseur absent de toute requête : seul `$cursor` compte.
        $name = 'listing_cursor';

        try {
            $page = $this->filtered($search, $filters)->cursorPaginate($this->perPage(), ['*'], $name, Cursor::fromEncoded($cursor));
        } catch (UnexpectedValueException|TypeError) {
            $page = $this->filtered($search, $filters)->cursorPaginate($this->perPage(), ['*'], $name);
        }
        $this->served($page->getCollection());

        return $page;
    }

    /**
     * Les enregistrements d'une page servie à l'app : rien par défaut. Une liste y compte ce qu'elle affiche (les vues
     * des partenaires, décision 2026-11-25-compteurs-partenaires).
     *
     * @param  Collection<int, Model>  $records
     */
    protected function served(Collection $records): void {}

    /**
     * Les filtres proposés par la liste, en champs (décision
     * 2026-11-19-contrat-filtres-vitrine) ; aucun par défaut. Les valeurs reçues
     * passent par `validFilters()` avant `applyFilters()`.
     *
     * @return list<Field>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Restreint `$query` aux valeurs de filtres (déjà validées, jamais vides). Sans
     * effet par défaut.
     *
     * @param  Builder<covariant Model>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void {}

    /**
     * Les filtres reçus (`?filter[city]=Douala`) que la liste connaît, non vides et
     * valides selon leur champ ; un filtre inconnu, vide ou invalide est ignoré.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array<string, mixed>
     */
    final public function validFilters(array $raw): array
    {
        $valid = [];

        foreach ($this->filters() as $field) {
            $value = $raw[$field->name] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (Validator::make([$field->name => $value], $field->validationRules())->passes()) {
                $valid[$field->name] = $value;
            }
        }

        return $valid;
    }

    /**
     * Enregistrements épinglés en tête de la première page, hors pagination (les
     * offres en vedette, décision 2026-11-19-contrat-vedette-favoris) ; aucun par
     * défaut. `query()` doit les exclure, pour qu'ils ne reviennent pas plus loin.
     * Peu nombreux : au plus `pinnedLimit()`.
     *
     * @return Builder<covariant Model>|null
     */
    protected function pinned(): ?Builder
    {
        return null;
    }

    public function pinnedLimit(): int
    {
        return 20;
    }

    /**
     * Les épinglés à afficher : sur la première page seulement, restreints à la recherche.
     *
     * @param  array<string, mixed>  $filters  filtres validés (`validFilters()`)
     * @return list<Model>
     */
    final public function pinnedItems(?string $cursor = null, ?string $search = null, array $filters = []): array
    {
        $query = $this->pinned();

        if ($query === null || filled($cursor)) {
            return [];
        }

        $term = Str::squish((string) $search);
        if ($term !== '' && $this->searchPlaceholder() !== null) {
            $query->where(fn (Builder $group) => $this->search($group, $term));
        }
        if ($filters !== []) {
            $query->where(fn (Builder $group) => $this->applyFilters($group, $filters));
        }

        $records = $query->limit($this->pinnedLimit())->get();
        $this->served($records);

        return $records->all();
    }

    /**
     * Texte d'aide du champ de recherche, traduit. Une liste qui en donne un est
     * cherchable et doit définir `search()` ; aucun par défaut.
     */
    public function searchPlaceholder(): ?string
    {
        return null;
    }

    /**
     * Restreint `$query` aux enregistrements qui correspondent à `$term` (déjà
     * nettoyé, jamais vide). Sans effet par défaut.
     *
     * @param  Builder<covariant Model>  $query
     */
    protected function search(Builder $query, string $term): void {}

    /**
     * Ajoute à `$query`, en « ou », une condition par colonne : la colonne
     * contient `$term`, sans casse, `%` et `_` cherchés tels quels.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<string>  $columns
     */
    final protected function orWhereContains(Builder $query, array $columns, string $term): void
    {
        ContainsSearch::orWhere($query, $columns, $term);
    }

    /**
     * La requête de la liste, restreinte à la recherche si la liste est
     * cherchable et que la recherche n'est pas vide.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<covariant Model>|Relation<covariant Model, covariant Model, mixed>
     */
    private function filtered(?string $search, array $filters = []): Builder|Relation
    {
        $query = $this->query();
        $term = Str::squish((string) $search);
        $builder = $query instanceof Relation ? $query->getQuery() : $query;

        if ($term !== '' && $this->searchPlaceholder() !== null) {
            $builder->where(fn (Builder $group) => $this->search($group, $term));
        }
        if ($filters !== []) {
            $builder->where(fn (Builder $group) => $this->applyFilters($group, $filters));
        }

        return $query;
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
     * Le bouton de la barre fixe en bas de la liste (décision
     * 2026-11-19-contrat-reservations-logements-profil) : son libellé, et le
     * formulaire qu'il ouvre ou l'écran de l'app (`/`) ; aucun par défaut.
     *
     * @return array{label: string, form: string|null, link: string|null}|null
     */
    public function footer(): ?array
    {
        return null;
    }

    /**
     * Les actions de chaque carte (Voir, Éditer, ses boutons, Retirer) regroupées dans un menu
     * déroulant « Actions » (décision 2026-11-21-actions-en-menu) ; non par défaut.
     */
    public function actionsMenu(): bool
    {
        return false;
    }

    /**
     * Le titre selon les filtres choisis (« Demandes · Studio meublé ») ; le titre
     * par défaut.
     *
     * @param  array<string, mixed>  $filters  filtres validés
     */
    public function titleFor(array $filters): string
    {
        return $this->title();
    }

    /**
     * Un intitulé affiché sous les filtres, en tête des items, selon les filtres choisis : un titre et, dessous,
     * un sous-titre facultatif (le logement des « Demandes » et sa ligne du type, décisions
     * 2026-11-21-demandes-titre-sous-filtres et 2026-11-21-demandes-d-une-offre) ; `null` par défaut : aucun.
     *
     * @param  array<string, mixed>  $filters  filtres validés
     * @return array{title: string|null, subtitle: string|null}|null
     */
    public function headingFor(array $filters, ?string $search = null): ?array
    {
        return null;
    }

    /**
     * Le nombre d'enregistrements de la liste, recherche et filtres compris (« 3 demandes »).
     *
     * @param  array<string, mixed>  $filters  filtres validés
     */
    final protected function countFor(?string $search, array $filters): int
    {
        return $this->filtered($search, $filters)->count();
    }

    /**
     * La liste est-elle proposée ? Indisponible, elle répond comme une liste
     * inconnue (404). Disponible par défaut.
     */
    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * Qui peut demander la liste. Par défaut : un compte connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }
}
