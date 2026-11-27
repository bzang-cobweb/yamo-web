<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\NotificationSettings;

/**
 * « Notifications », depuis le menu du profil (`PATCH /api/v1/auth/me/notifications`, décision
 * 2026-11-27-notifications-du-profil) : les canaux de l'activité du compte et des rappels (e-mail, SMS, push, tous
 * cochés par défaut), puis les offres et les nouveautés (oui par défaut).
 */
class NotificationSettingsForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.notifications';
    }

    public function fields(): array
    {
        $settings = $this->user instanceof User ? $this->user->notificationSettings() : NotificationSettings::defaults();

        return [
            Field::checkboxGroup(NotificationSettings::ACCOUNT_ACTIVITY)
                ->label(__('auth::notifications.account_activity'))
                ->help(__('auth::notifications.account_activity_help'))
                ->options(self::channelOptions())
                ->default($settings[NotificationSettings::ACCOUNT_ACTIVITY]),
            Field::checkboxGroup(NotificationSettings::REMINDERS)
                ->label(__('auth::notifications.reminders'))
                ->help(__('auth::notifications.reminders_help'))
                ->options(self::channelOptions())
                ->default($settings[NotificationSettings::REMINDERS]),
            Field::switch(NotificationSettings::OFFERS)
                ->label(__('auth::notifications.offers'))
                ->help(__('auth::notifications.offers_help'))
                ->default($settings[NotificationSettings::OFFERS]),
            Field::switch(NotificationSettings::NEWS)
                ->label(__('auth::notifications.news'))
                ->help(__('auth::notifications.news_help'))
                ->default($settings[NotificationSettings::NEWS]),
        ];
    }

    /**
     * E-mail, SMS et push ; un canal de compte désactivé (`AuthChannels`) n'est pas proposé.
     *
     * @return array<string, string>
     */
    public static function channelOptions(): array
    {
        return array_filter([
            NotificationSettings::EMAIL => AuthChannels::email() ? __('auth::notifications.channels.email') : null,
            NotificationSettings::SMS => AuthChannels::phone() ? __('auth::notifications.channels.sms') : null,
            NotificationSettings::PUSH => __('auth::notifications.channels.push'),
        ]);
    }

    public function title(): string
    {
        return __('auth::notifications.title');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.notifications.update', SuccessAction::PROFILE, __('auth::mobile.profile.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
