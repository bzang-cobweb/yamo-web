<?php

namespace Modules\Form\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Form\Enums\FieldType;
use Modules\Form\Http\Resources\FormUploadResource;
use Modules\Form\Models\FormUpload;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

class FormUploadController
{
    /**
     * Envoyer un fichier pour le champ `{field}` (de type `upload`), avant
     * d'enregistrer le formulaire (décision 2026-11-19-contrat-champ-upload-photos).
     * Le fichier (`file`, multipart) est validé selon les règles du champ : types,
     * taille. Il est gardé 24 h au nom du compte ; son identifiant se met dans la
     * valeur du champ. 404 si le champ n'existe pas ou n'est pas un champ de fichiers.
     */
    public function store(Request $request, Form $form, string $field): JsonResponse
    {
        $definition = collect($form->fields())->first(fn (Field $item): bool => $item->name === $field && $item->type === FieldType::UPLOAD);
        abort_if($definition === null, 404);

        $request->validate(['file' => $definition->fileRules()], [], ['file' => $definition->getLabel() ?? $field]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $uuid = (string) Str::uuid();

        $upload = FormUpload::query()->create([
            'uuid' => $uuid,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'form' => $form->key(),
            'field' => $field,
            'path' => (string) $file->storeAs('form-uploads', $uuid, FormUpload::DISK),
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        return (new FormUploadResource($upload))->response()->setStatusCode(201);
    }
}
