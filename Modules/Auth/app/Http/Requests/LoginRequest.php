<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Forms\LoginForm;

/**
 * Connexion de l'application mobile. Les règles des champs saisis viennent du
 * formulaire `auth.login` que l'app affiche ; `device_name`, renseigné par l'app,
 * nomme le jeton créé.
 */
class LoginRequest extends FormRequest
{
    /**
     * Route publique, limitée en fréquence (`throttle:auth-login`).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...(new LoginForm)->rules(),
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...(new LoginForm)->attributes(),
            'device_name' => __('auth::user.device_name'),
        ];
    }
}
