<?php

namespace Bza\Form\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Bza\Form\Support\Field;
use Bza\Form\Support\Listing;

/**
 * A list, as the mobile app displays it (decision
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
            /** Heading under the filters, above the items: a title and a subtitle; `null`: none. */
            'heading' => $this->resource->headingFor($filters, is_string($search) ? $search : null),
            /** Text displayed when the list is empty. */
            'empty' => $this->resource->emptyText(),
            /** Create button: label and form opened; absent for a read-only list. */
            'create' => $this->resource->create(),
            /** Button of the fixed bottom bar: label, form or app screen opened; `null`: none. */
            'footer' => $this->resource->footer(),
            'menu' => $this->resource->actionsMenu(),
            /** Help text of the search field; `null`: list not searchable, `?search=` ignored. */
            'search' => $this->resource->searchPlaceholder(),
            /** Filters offered, as form fields (`?filter[name]=value`); empty: list without filters. */
            'filters' => array_map(fn (Field $field): array => $field->asFilter()->toArray(), $this->resource->filters()),
            /** Cursor of the next page, to send back as `?cursor=`; `null`: nothing more after. */
            'next_cursor' => $page->nextCursor()?->encode(),
            /** Page items: `image` is the photo displayed above the title; `edit` opens the form bound to the record (`?record=`), `delete` describes the removal. */
            'items' => collect($this->resource->pinnedItems(is_string($cursor) ? $cursor : null, is_string($search) ? $search : null, $filters))
                // Pinned items at the top of the first page (featured offers), then the page.
                ->concat($page->getCollection())
                ->map(fn (Model $record): array => $this->resource->item($record)->toArray())->values()->all(),
        ];
    }
}
