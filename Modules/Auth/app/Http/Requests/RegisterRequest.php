<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\RegisterForm;
use Modules\Auth\Services\RegisterService;

/**
 * Créer son compte : règles du formulaire `auth.register` pour le canal de l'identifiant prouvé par le défi (l'autre
 * contact, facultatif et unique), plus le défi et le nom de l'appareil.
 */
class RegisterRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): RegisterForm
    {
        $challenge = $this->input('challenge');

        return new RegisterForm(channel: is_string($challenge) && $challenge !== '' ? (RegisterService::channelOf($challenge) ?? '') : '');
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'challenge' => ['required', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
