<?php

namespace Modules\Form\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
     * @return array{name: string, title: string, empty: string|null, create: array{label: string, form: string}|null, next_cursor: string|null, items: list<array{id: string, title: string, subtitle: string|null, edit: array{form: string, record: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: 'profile'|'sign-out'|'back', message: string}}|null}>}
     */
    public function toArray(Request $request): array
    {
        $cursor = $request->query('cursor');
        $page = $this->resource->page(is_string($cursor) ? $cursor : null);

        return [
            'name' => $this->resource->key(),
            'title' => $this->resource->title(),
            /** Texte affiché quand la liste est vide. */
            'empty' => $this->resource->emptyText(),
            /** Bouton de création : libellé et formulaire ouvert ; absent pour une liste en lecture seule. */
            'create' => $this->resource->create(),
            /** Curseur de la page suivante, à renvoyer en `?cursor=` ; `null` : plus rien après. */
            'next_cursor' => $page->nextCursor()?->encode(),
            /** Items de la page : `edit` ouvre le formulaire lié à l'enregistrement (`?record=`), `delete` décrit le retrait. */
            'items' => $page->getCollection()->map(fn (Model $record): array => $this->resource->item($record)->toArray())->values()->all(),
        ];
    }
}
