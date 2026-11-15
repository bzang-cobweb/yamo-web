<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Forms\ProfileLocaleForm;
use Modules\Auth\Models\User;

/**
 * Modification de son propre compte depuis l'app mobile. Seuls les champs des
 * formulaires de profil sont acceptés (aujourd'hui : la langue) ; tout autre
 * champ envoyé est ignoré.
 */
class UpdateMeRequest extends FormRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->form()->rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->form()->attributes();
    }

    private function form(): ProfileLocaleForm
    {
        return new ProfileLocaleForm($this->user());
    }
}
