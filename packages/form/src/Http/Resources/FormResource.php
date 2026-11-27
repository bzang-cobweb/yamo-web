<?php

namespace Bza\Form\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Step;

/**
 * Definition of a form, as the mobile app displays it.
 *
 * @property Form $resource
 */
class FormResource extends JsonResource
{
    /**
     * @return array{name: string, title: string|null, subtitle: string|null, title_align: 'start'|'center', fields: list<array{name: string, type: string, label: string|null, placeholder: string|null, help: string|null, required: bool, default: mixed, upload: array{accept: list<string>, max_size_kb: int, max_files: int, endpoint: string, files: list<array{id: string, url: string}>}|null, options: list<array{value: string, label: string}>}>, steps: list<array{title: string|null, fields: list<string>}>, submit: array{label: string, method?: string, endpoint?: string, destructive?: bool, success?: array{action: 'profile'|'sign-out'|'back', message: string}}}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->key(),
            'title' => $this->resource->title(),
            'subtitle' => $this->resource->subtitle(),
            /** The top bar title; `null`: the title (decision 2026-11-22-formulaire-titre-navigation). */
            'navigation_title' => $this->resource->navigationTitle(),
            /** A description under the subtitle, in grey (an offer's title); `null`: none. */
            'description' => $this->resource->description(),
            /** A note under the title, set apart (a warning); `null`: none. */
            'notice' => $this->resource->notice(),
            /** Alignment of the title, the subtitle and the step titles: `start` or `center`. */
            'title_align' => $this->resource->titleAlign(),
            // A file field receives the address to send each file to (relative to the API).
            'fields' => array_map(fn (Field $field): array => $this->field($field), $this->resource->fields()),
            /** Steps (title, field names); empty for a single-part form. */
            'steps' => array_map(fn (Step $step): array => $step->toArray(), $this->resource->steps()),
            /** Button label; for a form the app sends without knowing it, also `method`, `endpoint` (relative to the API), `destructive` and `success`. */
            'submit' => ['label' => $this->resource->submitLabel(), ...($this->resource->submission()?->toArray() ?? [])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function field(Field $field): array
    {
        $data = $field->toArray();

        if (is_array($data['upload'])) {
            $data['upload']['endpoint'] = '/v1/forms/'.rawurlencode($this->resource->key()).'/uploads/'.rawurlencode($field->name);
        }

        return $data;
    }
}
