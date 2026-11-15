<?php

namespace Modules\Form\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Définition d'un formulaire, telle que l'app mobile l'affiche.
 *
 * @property Form $resource
 */
class FormResource extends JsonResource
{
    /**
     * @return array{name: string, fields: list<array{name: string, type: string, label: string|null, placeholder: string|null, help: string|null, required: bool, default: mixed, options: list<array{value: string, label: string}>}>, submit: array{label: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->key(),
            'fields' => array_map(fn (Field $field): array => $field->toArray(), $this->resource->fields()),
            'submit' => ['label' => $this->resource->submitLabel()],
        ];
    }
}
