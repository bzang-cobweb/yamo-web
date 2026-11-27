<?php

namespace Bza\Form\Support;

use Bza\Form\Support\ContainsSearch;
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
 * A list described by the server, which the app displays without knowing it in advance
 * (decision 2026-11-18-adresses-liste-generique): title, text when it is
 * empty, items, and a create button that opens a form. Each item can
 * open in a form and be removed.
 *
 * Items are served in pages, tracked by a cursor (decision
 * 2026-11-18-contrat-liste-infinie-role-admin): a list declares its query
 * (`query()`, in a stable order) and how a record is formatted
 * (`item()`); pagination is done here. A list can make itself searchable
 * (decision 2026-11-18-recherche-dans-la-liste): `searchPlaceholder()` and
 * `search()`.
 *
 * `List` being a PHP reserved word, the class is named `Listing`. Each list
 * is declared in the module that owns it and added to the registry
 * (`ListingRegistry::register()`).
 */
abstract class Listing
{
    public function __construct(protected ?Authenticatable $user = null) {}

    /**
     * Public name of the list, used in the URL (`/api/v1/lists/{name}`).
     */
    abstract public static function name(): string;

    /**
     * The name, from an instance (serialization).
     */
    final public function key(): string
    {
        return static::name();
    }

    /**
     * Screen title, translated.
     */
    abstract public function title(): string;

    /**
     * The records of the list, in display order. The order must
     * end with a unique column (the `id`): that is what the cursor follows.
     *
     * @return Builder<covariant Model>|Relation<covariant Model, covariant Model, mixed>
     */
    abstract public function query(): Builder|Relation;

    /**
     * A record, formatted for the app.
     */
    abstract public function item(Model $record): ListItem;

    /**
     * Number of items per page.
     */
    public function perPage(): int
    {
        return 20;
    }

    /**
     * A page of items: the first one without a cursor, otherwise the one following the
     * cursor. A cursor that is unreadable or does not match the order of the
     * list serves the first page.
     *
     * @param  array<string, mixed>  $filters  validated filters (`validFilters()`)
     * @return CursorPaginator<int, Model>
     */
    final public function page(?string $cursor = null, ?string $search = null, array $filters = []): CursorPaginator
    {
        // Cursor name absent from any request: only `$cursor` counts.
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
     * The records of a page served to the app: nothing by default. A list counts there what it displays (the views
     * of partners, decision 2026-11-25-compteurs-partenaires).
     *
     * @param  Collection<int, Model>  $records
     */
    protected function served(Collection $records): void {}

    /**
     * The filters offered by the list, as fields (decision
     * 2026-11-19-contrat-filtres-vitrine); none by default. Received values
     * go through `validFilters()` before `applyFilters()`.
     *
     * @return list<Field>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Restricts `$query` to the filter values (already validated, never empty). No
     * effect by default.
     *
     * @param  Builder<covariant Model>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void {}

    /**
     * The received filters (`?filter[city]=Douala`) the list knows, non-empty and
     * valid according to their field; an unknown, empty or invalid filter is ignored. A
     * required filter that was not sent takes its default value.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array<string, mixed>
     */
    final public function validFilters(array $raw): array
    {
        $fields = $this->filters();
        // A required filter that was not sent gets its default value: the region of the search (decision
        // 2026-11-25-region-obligatoire-recherche).
        $data = $raw;
        foreach ($fields as $field) {
            $value = $data[$field->name] ?? null;
            if (($value === null || $value === '' || $value === []) && $field->isRequired()) {
                $data[$field->name] = $field->defaultValue();
            }
        }

        $valid = [];
        foreach ($fields as $field) {
            $value = $data[$field->name] ?? null;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            // A dependent list is checked against its parent (the city within the chosen region, decision
            // 2026-11-25-ville-selon-region); with no parent chosen, any option of the list is accepted.
            $parent = $field->parentField();
            $check = $parent !== null && blank($data[$parent] ?? null) ? (clone $field)->dependsOn(null) : $field;
            // Hidden by its condition (the city with no region chosen): ignored. Visibility is checked here, chips
            // included, which the `exclude_unless` rule cannot read (decision 2026-11-26-region-ville-en-puces).
            if (! $check->isVisibleFor($data)) {
                continue;
            }
            if (Validator::make([...$data, $field->name => $value], $check->withoutVisibility()->validationRules())->passes()) {
                $valid[$field->name] = $value;
            }
        }

        return $valid;
    }

    /**
     * Records pinned at the top of the first page, outside pagination (the
     * featured offers, decision 2026-11-19-contrat-vedette-favoris); none by
     * default. `query()` must exclude them, so they do not come back further on.
     * Few of them: at most `pinnedLimit()`.
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
     * The pinned records to display: on the first page only, restricted to the search.
     *
     * @param  array<string, mixed>  $filters  validated filters (`validFilters()`)
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
     * Placeholder text of the search field, translated. A list that provides one is
     * searchable and must define `search()`; none by default.
     */
    public function searchPlaceholder(): ?string
    {
        return null;
    }

    /**
     * Restricts `$query` to the records matching `$term` (already
     * cleaned, never empty). No effect by default.
     *
     * @param  Builder<covariant Model>  $query
     */
    protected function search(Builder $query, string $term): void {}

    /**
     * Adds to `$query`, with "or", one condition per column: the column
     * contains `$term`, case-insensitive, `%` and `_` matched literally.
     *
     * @param  Builder<covariant Model>  $query
     * @param  list<string>  $columns
     */
    final protected function orWhereContains(Builder $query, array $columns, string $term): void
    {
        ContainsSearch::orWhere($query, $columns, $term);
    }

    /**
     * The query of the list, restricted to the search if the list is
     * searchable and the search is not empty.
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
        $this->constrain($builder, $filters);

        return $query;
    }

    /**
     * A restriction always applied, whether filters are chosen or not (the reviews of a single offer, none without
     * an offer: decision 2026-11-25-liste-avis-generique). Nothing by default.
     *
     * @param  Builder<covariant Model>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function constrain(Builder $query, array $filters): void {}

    /**
     * Text displayed when the list is empty, translated; none by default.
     */
    public function emptyText(): ?string
    {
        return null;
    }

    /**
     * Create button: its label and the form it opens; none by
     * default (read-only list).
     *
     * @return array{label: string, form: string}|null
     */
    public function create(): ?array
    {
        return null;
    }

    /**
     * The button of the fixed bar at the bottom of the list (decision
     * 2026-11-19-contrat-reservations-logements-profil): its label, and the
     * form it opens or the app screen (`/`); none by default.
     *
     * @return array{label: string, form: string|null, link: string|null}|null
     */
    public function footer(): ?array
    {
        return null;
    }

    /**
     * The actions of each card (View, Edit, its buttons, Remove) grouped in an « Actions »
     * dropdown menu (decision 2026-11-21-actions-en-menu); off by default.
     */
    public function actionsMenu(): bool
    {
        return false;
    }

    /**
     * The title according to the chosen filters (« Demandes · Studio meublé »); the default
     * title by default.
     *
     * @param  array<string, mixed>  $filters  validated filters
     */
    public function titleFor(array $filters): string
    {
        return $this->title();
    }

    /**
     * A heading displayed below the filters, above the items, according to the chosen filters: a title and, below it,
     * an optional subtitle (the accommodation of the « Demandes » and its type line, decisions
     * 2026-11-21-demandes-titre-sous-filtres and 2026-11-21-demandes-d-une-offre); `null` by default: none.
     *
     * @param  array<string, mixed>  $filters  validated filters
     * @return array{title: string|null, subtitle: string|null}|null
     */
    public function headingFor(array $filters, ?string $search = null): ?array
    {
        return null;
    }

    /**
     * The number of records in the list, search and filters included (« 3 demandes »).
     *
     * @param  array<string, mixed>  $filters  validated filters
     */
    final protected function countFor(?string $search, array $filters): int
    {
        return $this->filtered($search, $filters)->count();
    }

    /**
     * Is the list offered? When unavailable, it responds like an unknown
     * list (404). Available by default.
     */
    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * Who may request the list. By default: a logged-in account.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }
}
