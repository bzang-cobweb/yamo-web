<?php

namespace Modules\Form\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;

/**
 * Une liste, telle que l'app mobile l'affiche (décision
 * 2026-11-18-adresses-liste-generique).
 *
 * @property Listing $resource
 */
class ListingResource extends JsonResource
{
    /**
     * @return array{name: string, title: string, heading: array{title: string|null, subtitle: string|null}|null, empty: string|null, create: array{label: string, form: string}|null, footer: array{label: string, form: string|null, link: string|null}|null, search: string|null, filters: list<array<string, mixed>>, next_cursor: string|null, items: list<array{id: string, title: string, subtitle: string|null, image: string|null, rating: array{average: float, count: int}|null, highlight: string|null, favorite: array{active: bool, endpoint: string}|null, link: string|null, edit: array{form: string, record: string}|null, actions: list<array{label: string, style: string, confirm: string|null, submission: array<string, mixed>|null, form: array{form: string, record: string}|null}>, tag: array{label: string, tone: string}|null, counter: array{label: string, list: string, filter: array<string, string>}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: 'profile'|'sign-out'|'back', message: string}}|null}>}
     */
    public function toArray(Request $request): array
    {
        $cursor = $request->query('cursor');
        $search = $request->query('search');
        $rawFilters = $request->query('filter');
        $filters = $this->resource->validFilters(is_array($rawFilters) ? $rawFilters : []);
        $page = $this->resource->page(is_string($cursor) ? $cursor : null, is_string($search) ? $search : null, $filters);

        return [
            'name' => $this->resource->key(),
            'title' => $this->resource->titleFor($filters),
            /** Intitulé sous les filtres, en tête des items : un titre et un sous-titre ; `null` : aucun. */
            'heading' => $this->resource->headingFor($filters, is_string($search) ? $search : null),
            /** Texte affiché quand la liste est vide. */
            'empty' => $this->resource->emptyText(),
            /** Bouton de création : libellé et formulaire ouvert ; absent pour une liste en lecture seule. */
            'create' => $this->resource->create(),
            /** Bouton de la barre fixe en bas : libellé, formulaire ou écran de l'app ouvert ; `null` : aucun. */
            'footer' => $this->resource->footer(),
            'menu' => $this->resource->actionsMenu(),
            /** Texte d'aide du champ de recherche ; `null` : liste non cherchable, `?search=` ignoré. */
            'search' => $this->resource->searchPlaceholder(),
            /** Filtres proposés, en champs de formulaire (`?filter[nom]=valeur`) ; vide : liste sans filtres. */
            'filters' => array_map(fn (Field $field): array => $field->asFilter()->toArray(), $this->resource->filters()),
            /** Curseur de la page suivante, à renvoyer en `?cursor=` ; `null` : plus rien après. */
            'next_cursor' => $page->nextCursor()?->encode(),
            /** Items de la page : `image` est la photo affichée au-dessus du titre ; `edit` ouvre le formulaire lié à l'enregistrement (`?record=`), `delete` décrit le retrait. */
            'items' => collect($this->resource->pinnedItems(is_string($cursor) ? $cursor : null, is_string($search) ? $search : null, $filters))
                // Les épinglés en tête de la première page (offres en vedette), puis la page.
                ->concat($page->getCollection())
                ->map(fn (Model $record): array => $this->resource->item($record)->toArray())->values()->all(),
        ];
    }
}
