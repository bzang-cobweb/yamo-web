<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * L'étape du mot de passe (`POST /auth/session`). Le mot de passe est validé ensuite par le service, avec les règles du
 * formulaire de l'étape (`auth.session.password` ou `auth.session.new-password`), que seul le défi désigne.
 */
class SessionRequest extends FormRequest
{
    /**
     * Route publique, limitée en fréquence (`throttle:auth-login`) ; le défi validé par un code fait foi.
     */
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
            /** Le défi dont le code vient d'être validé. */
            'challenge' => ['required', 'uuid'],
            'password' => ['required', 'string', 'max:255'],
            /** Avec un nouveau mot de passe (compte sans mot de passe, inscription, mot de passe oublié). */
            'password_confirmation' => ['nullable', 'string'],
            /** « Mot de passe oublié ? » : un compte qui a un mot de passe en choisit un nouveau. */
            'reset' => ['sometimes', 'boolean'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
