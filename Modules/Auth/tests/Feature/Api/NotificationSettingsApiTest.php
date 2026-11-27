<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Tests\TestCase;

/**
 * « Notifications » dans le menu du profil (décision 2026-11-27-notifications-du-profil) : activité du compte et
 * rappels (e-mail, SMS, push, tous cochés par défaut), offres et nouveautés (oui par défaut) ; l'activité du compte
 * décide des canaux des notifications envoyées.
 */
class NotificationSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_the_menu_links_to_notifications_after_privacy_with_a_bell(): void
    {
        $menu = $this->asUser(User::factory()->create(['locale' => 'fr']))->getJson('/api/v1/auth/me/menu')->assertOk()->json('data');
        $keys = array_column($menu, 'key');
        $link = $menu[array_search('notifications', $keys, true)];

        $this->assertSame(array_search('privacy', $keys, true) + 1, array_search('notifications', $keys, true));
        $this->assertSame('Notifications', $link['label']);
        $this->assertSame('auth.profile.notifications', $link['form']);
        $this->assertSame(['ios' => 'bell', 'android' => 'notifications'], $link['icon']);
    }

    public function test_the_form_is_filled_with_the_defaults(): void
    {
        $fields = collect($this->asUser(User::factory()->create(['locale' => 'fr']))->getJson('/api/v1/forms/auth.profile.notifications')
            ->assertOk()
            ->assertJsonPath('data.title', 'Notifications')
            ->json('data.fields'))->keyBy('name');

        $this->assertSame(['account_activity', 'reminders', 'offers', 'news'], $fields->keys()->all());
        $this->assertSame('checkbox_group', $fields['account_activity']['type']);
        $this->assertSame('Activité du compte', $fields['account_activity']['label']);
        $this->assertSame(['email', 'sms', 'push'], $fields['account_activity']['default']);
        $this->assertSame(['email', 'sms', 'push'], $fields['reminders']['default']);
        $this->assertSame('switch', $fields['offers']['type']);
        $this->assertTrue($fields['offers']['default']);
        $this->assertTrue($fields['news']['default']);
    }

    public function test_the_choices_are_saved_and_shown_again(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/notifications', [
            'account_activity' => ['email', 'push'],
            'reminders' => [],
            'offers' => false,
            'news' => true,
        ])->assertOk();

        $this->assertSame(['account_activity' => ['email', 'push'], 'reminders' => [], 'offers' => false, 'news' => true], $user->fresh()->notification_settings);

        $fields = collect($this->asUser($user)->getJson('/api/v1/forms/auth.profile.notifications')->json('data.fields'))->keyBy('name');
        $this->assertSame(['email', 'push'], $fields['account_activity']['default']);
        $this->assertSame([], $fields['reminders']['default']);
        $this->assertFalse($fields['offers']['default']);
    }

    public function test_invalid_choices_are_refused(): void
    {
        $this->asUser(User::factory()->create(['locale' => 'fr']))
            ->patchJson('/api/v1/auth/me/notifications', ['account_activity' => ['pigeon'], 'offers' => 'peut-être'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_activity.0', 'offers']);
    }

    public function test_requires_authentication(): void
    {
        $this->patchJson('/api/v1/auth/me/notifications', ['offers' => false])->assertUnauthorized();
    }

    public function test_the_account_activity_choice_decides_the_mail_and_sms_channels(): void
    {
        $user = User::factory()->make(['email_verified_at' => now(), 'phone' => '+41791234567', 'phone_verified_at' => now()]);
        $this->assertSame(['mail', SmsChannel::class], $user->notificationChannels());

        $user->notification_settings = ['account_activity' => ['sms']];
        $this->assertSame([SmsChannel::class], $user->notificationChannels());

        $user->notification_settings = ['account_activity' => []];
        $this->assertSame([], $user->notificationChannels());
    }
}
