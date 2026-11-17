<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Récupérer son compte en attente de suppression : le défi de connexion dont le
 * code vient d'être validé, et le nom de l'appareil à connecter.
 */
class RestoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'challenge' => ['required', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
