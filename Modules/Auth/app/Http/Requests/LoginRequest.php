<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Connexion de l'application mobile : identifiants et nom de l'appareil,
 * qui nomme le jeton créé.
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('auth::user.email'),
            'password' => __('auth::user.password'),
            'device_name' => __('auth::user.device_name'),
        ];
    }
}
