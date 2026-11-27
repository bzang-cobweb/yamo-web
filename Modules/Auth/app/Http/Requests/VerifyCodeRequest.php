<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Forms\CodeForm;

/**
 * Valider un code : règles du formulaire `auth.code`, plus le défi (et le canal
 * quand il y en a plusieurs) et le nom de l'appareil pour une connexion.
 */
class VerifyCodeRequest extends FormBackedRequest
{
    /**
     * Route publique limitée en fréquence ; un défi de changement d'e-mail ou de
     * téléphone n'est validé que pour le compte qui l'a ouvert (service).
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): CodeForm
    {
        return new CodeForm;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'challenge' => ['required', 'uuid'],
            'channel' => ['nullable', Rule::enum(VerificationChannel::class)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
