<?php

namespace Modules\Auth\Forms;

use App\Support\Locales;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Langue du compte, modifiable depuis le profil de l'app mobile
 * (`PATCH /api/v1/auth/me`). Réservé à un compte connecté.
 */
class ProfileLocaleForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.locale';
    }

    public function fields(): array
    {
        return [
            Field::select('locale')
                ->label(__('auth::user.locale'))
                ->options(Locales::options())
                ->default($this->user instanceof User ? $this->user->locale : Locales::default())
                ->required(),
        ];
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
