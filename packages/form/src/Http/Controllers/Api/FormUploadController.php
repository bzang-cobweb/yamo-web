<?php

namespace Bza\Form\Http\Controllers\Api;

use Bza\Form\Enums\FieldType;
use Bza\Form\Http\Resources\FormUploadResource;
use Bza\Form\Models\FormUpload;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\FormAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FormUploadController
{
    /**
     * Upload a file for field `{field}` (of type `upload`), before
     * saving the form (decision 2026-11-19-contrat-champ-upload-photos).
     * The file (`file`, multipart) is validated against the field's rules: types,
     * size. It is kept 24 h under the account's name (no account for a visitor, when allowed); its identifier goes into the
     * field's value. 404 if the field does not exist or is not a file field.
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
            // The requesting account (guard `form.guard`); `null` for a visitor when guests may upload.
            'user_id' => FormAuth::id(),
            'form' => $form->key(),
            'field' => $field,
            'path' => (string) $file->storeAs('form-uploads', $uuid, FormUpload::DISK),
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        return (new FormUploadResource($upload))->response()->setStatusCode(201);
    }
}
