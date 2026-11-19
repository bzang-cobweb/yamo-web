<?php

namespace Modules\Form\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Form\Models\FormUpload;

/**
 * Un fichier envoyé pour un champ `upload` : l'identifiant à mettre dans la valeur du champ.
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
