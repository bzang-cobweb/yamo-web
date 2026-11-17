<?php

namespace Modules\Form\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Step;

/**
 * Définition d'un formulaire, telle que l'app mobile l'affiche.
 *
 * @property Form $resource
 */
class FormResource extends JsonResource
{
    /**
     * @return array{name: string, title: string|null, subtitle: string|null, fields: list<array{name: string, type: string, label: string|null, placeholder: string|null, help: string|null, required: bool, default: mixed, options: list<array{value: string, label: string}>}>, steps: list<array{title: string|null, fields: list<string>}>, submit: array{label: string, method?: string, endpoint?: string, destructive?: bool, success?: array{action: 'profile'|'sign-out', message: string}}}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->key(),
            'title' => $this->resource->title(),
            'subtitle' => $this->resource->subtitle(),
            'fields' => array_map(fn (Field $field): array => $field->toArray(), $this->resource->fields()),
            /** Étapes (titre, noms des champs) ; vide pour un formulaire d'un seul tenant. */
            'steps' => array_map(fn (Step $step): array => $step->toArray(), $this->resource->steps()),
            /** Libellé du bouton ; pour un formulaire que l'app envoie sans le connaître, aussi `method`, `endpoint` (relatif à l'API), `destructive` et `success`. */
            'submit' => ['label' => $this->resource->submitLabel(), ...($this->resource->submission()?->toArray() ?? [])],
        ];
    }
}
