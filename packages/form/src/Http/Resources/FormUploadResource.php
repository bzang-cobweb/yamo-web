<?php

namespace Bza\Form\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Bza\Form\Models\FormUpload;

/**
 * A file sent for an `upload` field: the identifier to put in the field's value.
 *
 * @property FormUpload $resource
 */
class FormUploadResource extends JsonResource
{
    /**
     * @return array{id: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid,
        ];
    }
}
