<?php

namespace Modules\Auth\Forms;

use App\Support\Locales;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Préférences du compte, depuis le profil de l'app mobile
 * (`PATCH /api/v1/auth/me/preferences`) : aujourd'hui la langue.
 */
class PreferencesForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.preferences';
    }

    public function fields(): array
    {
        return [
            Field::select('locale')
                ->label(__('auth::user.locale'))
                ->help(__('auth::form.preferences.locale_help'))
                ->options(Locales::options())
                ->default($this->user instanceof User ? $this->user->locale : Locales::default())
                ->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.preferences.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
