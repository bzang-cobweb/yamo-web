<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;

/**
 * Envoi de sa propre photo de profil depuis l'app mobile (multipart, champ
 * `avatar`). Les règles vivent dans `AvatarService`, partagées avec le back-office.
 */
class UpdateAvatarRequest extends FormRequest
{
    /**
     * Même capacité que la garde de la route (`can:updateMe`).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'avatar' => AvatarService::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatar' => __('auth::user.avatar_attribute'),
        ];
    }
}
