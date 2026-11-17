<?php

namespace Modules\Auth\Tests\Feature;

use Livewire\Livewire;
use LogicException;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource;
use Modules\Auth\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Country;

/**
 * Canaux de compte réglés par la configuration (`AUTH_CHANNEL_EMAIL`,
 * `AUTH_CHANNEL_PHONE`) : tout ce qui touche à l'e-mail ou au téléphone suit ce
 * réglage (décision 2026-11-20-canaux-email-telephone).
 */
class AuthChannelsTest extends AuthFilamentTestCase
{
    use CapturesVerificationCodes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function channels(bool $email, bool $phone): void
    {
        config(['auth.channels' => ['email' => $email, 'phone' => $phone]]);
    }

    private function form(string $name): array
    {
        return $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/'.$name)->assertOk()->json('data');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fieldsOf(array $form): array
    {
        return collect($form['fields'])->keyBy('name')->all();
    }

    private function register(array $overrides = [])
    {
        return $this->postJson('/api/v1/auth/register', [
            'name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'gender' => 'female',
            'password' => 'Secret-password-1', 'password_confirmation' => 'Secret-password-1',
            ...$overrides,
        ]);
    }

    public function test_both_channels_keep_today_behaviour(): void
    {
        $register = $this->fieldsOf($this->form('auth.register'));
        $this->assertArrayHasKey('email', $register);
        $this->assertArrayHasKey('phone', $register);
        $this->assertFalse($register['email']['required']);
        $this->assertSame(__('auth::form.register.contact_help', [], 'fr'), $register['email']['help']);

        $login = $this->fieldsOf($this->form('auth.login'));
        // Sélecteur « E-mail | Téléphone », puis le champ choisi.
        $this->assertSame(['channel', 'email', 'phone', 'password'], array_keys($login));
        $this->assertSame('segmented', $login['channel']['type']);
        $this->assertSame(['field' => 'channel', 'value' => 'email'], $login['email']['visible_when']);
        $this->assertSame(['field' => 'channel', 'value' => 'phone'], $login['phone']['visible_when']);

        $this->getJson('/api/v1/app')->assertJsonPath('data.channels', ['email' => true, 'phone' => true]);
    }

    public function test_email_only(): void
    {
        $this->channels(email: true, phone: false);

        $form = $this->form('auth.register');
        $register = $this->fieldsOf($form);
        $this->assertArrayNotHasKey('phone', $register);
        $this->assertTrue($register['email']['required']);
        $this->assertNull($register['email']['help']);
        $this->assertSame(['email'], $form['steps'][1]['fields']);

        $login = $this->fieldsOf($this->form('auth.login'));
        $this->assertSame(['email', 'password'], array_keys($login));
        $this->assertSame('email', $login['email']['type']);
        $this->assertNull($login['email']['visible_when']);
        $this->assertSame(['email'], array_keys($this->fieldsOf($this->form('auth.password.forgot'))));
        $this->assertStringNotContainsString('téléphone', $this->form('auth.password.forgot')['subtitle']);

        // Un téléphone ne s'inscrit pas : l'e-mail est obligatoire, le numéro ignoré.
        $this->register(['phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('email')->assertJsonMissingValidationErrors('phone');

        $this->getJson('/api/v1/app')->assertJsonPath('data.channels', ['email' => true, 'phone' => false]);
    }

    public function test_phone_only(): void
    {
        $this->channels(email: false, phone: true);

        $register = $this->fieldsOf($this->form('auth.register'));
        $this->assertArrayNotHasKey('email', $register);
        $this->assertTrue($register['phone']['required']);

        // Le pays et le numéro, sur une ligne (champ téléphone), sans aide sur le format.
        $login = $this->fieldsOf($this->form('auth.login'));
        $this->assertSame(['phone', 'password'], array_keys($login));
        $this->assertSame('phone', $login['phone']['type']);
        $this->assertContains('CH', array_column($login['phone']['options'], 'value'));
        $this->assertSame(['phone'], array_keys($this->fieldsOf($this->form('auth.password.forgot'))));

        $this->register(['email' => 'marie@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone')->assertJsonMissingValidationErrors('email');
    }

    public function test_phone_only_signs_in_with_the_country_and_the_national_number(): void
    {
        $this->channels(email: false, phone: true);
        User::factory()->create([
            'email' => null, 'phone' => '+41791234567', 'phone_verified_at' => now(),
            'phone_country_id' => Country::query()->where('code', 'CH')->value('id'), 'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', ['phone' => '079 123 45 67', 'phone_country' => 'CH', 'password' => 'secret-password'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms');
        $this->postJson('/api/v1/auth/password/forgot', ['phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms');

        // Sans pays, le champ téléphone est incomplet.
        $this->postJson('/api/v1/auth/login', ['phone' => '079 123 45 67', 'password' => 'secret-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone_country');
    }

    public function test_email_only_refuses_an_identifier_that_is_not_an_email(): void
    {
        $this->channels(email: true, phone: false);

        $this->postJson('/api/v1/auth/login', ['email' => '+41 79 123 45 67', 'password' => 'secret-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_a_disabled_channel_cannot_be_added_or_changed(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->channels(email: true, phone: false);
        $this->withToken($token)->getJson('/api/v1/forms/auth.profile.phone')->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/auth/me/phone', ['phone' => '079 123 45 67', 'phone_country' => 'CH'])->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/forms/auth.profile.email')->assertOk();

        $this->channels(email: false, phone: true);
        $this->withToken($token)->getJson('/api/v1/forms/auth.profile.email')->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/auth/me/email', ['email' => 'new@example.com'])->assertForbidden();
    }

    public function test_an_existing_account_keeps_its_data_when_a_channel_is_disabled(): void
    {
        // Le compte n'est ni bloqué ni modifié : réactiver le canal suffit à ce qu'il
        // se connecte de nouveau avec son numéro.
        $this->channels(email: true, phone: true);
        User::factory()->create([
            'email' => null, 'phone' => '+41791234567', 'phone_verified_at' => now(),
            'phone_country_id' => Country::query()->where('code', 'CH')->value('id'), 'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', ['channel' => 'phone', 'phone' => '079 123 45 67', 'phone_country' => 'CH', 'password' => 'secret-password'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms');
    }

    public function test_at_least_one_channel_is_required(): void
    {
        $this->channels(email: false, phone: false);

        $this->expectException(LogicException::class);
        AuthChannels::enabled();
    }

    public function test_the_back_office_user_form_follows_the_channels(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);
        $this->channels(email: true, phone: false);

        Livewire::test(CreateUser::class)
            ->assertFormFieldVisible('email')
            ->assertFormFieldHidden('phone')
            ->fillForm(['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'email' => '', 'password' => 'Secret-password-1'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_the_back_office_global_search_follows_the_channels(): void
    {
        $this->assertSame(['name', 'first_name', 'last_name', 'email', 'phone'], UserResource::getGloballySearchableAttributes());

        $this->channels(email: false, phone: true);
        $this->assertSame(['name', 'first_name', 'last_name', 'phone'], UserResource::getGloballySearchableAttributes());

        $this->channels(email: true, phone: false);
        $this->assertSame(['name', 'first_name', 'last_name', 'email'], UserResource::getGloballySearchableAttributes());
    }

    public function test_a_single_active_country_is_chosen_and_locked_everywhere(): void
    {
        // Seul pays actif : la Suisse (créée dans setUp).
        $phone = $this->fieldsOf($this->form('auth.register'))['phone'];
        $this->assertTrue($phone['locked']);
        $this->assertSame(['country' => 'CH', 'number' => null], $phone['default']);

        $login = $this->fieldsOf($this->form('auth.login'));
        $this->assertTrue($login['phone']['locked']);

        $user = User::factory()->create(['country_id' => null]);
        $personal = $this->fieldsOf($this->withToken($user->createToken('t')->plainTextToken)->getJson('/api/v1/forms/auth.profile.personal')->json('data'));
        $this->assertTrue($personal['country_id']['locked']);
        $this->assertSame((string) Country::query()->value('id'), $personal['country_id']['default']);
        $this->assertFalse($personal['region_id']['locked']);

        // Un second pays actif : plus rien n'est verrouillé.
        Country::factory()->create(['code' => 'FR', 'dial_code' => '+33']);
        $this->assertFalse($this->fieldsOf($this->form('auth.register'))['phone']['locked']);
    }

    public function test_the_back_office_locks_a_single_active_country_and_saves_it(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);
        $switzerland = Country::query()->where('code', 'CH')->value('id');

        Livewire::test(CreateUser::class)
            ->assertFormFieldDisabled('country_id')
            ->assertFormFieldDisabled('phone_country_id')
            ->assertSchemaStateSet(['country_id' => $switzerland, 'phone_country_id' => $switzerland])
            ->fillForm(['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'gender' => 'female', 'email' => 'marie@example.com', 'phone' => '079 123 45 67', 'password' => 'Secret-password-1'])
            ->call('create')
            ->assertHasNoFormErrors();

        $marie = User::query()->where('name', 'marie')->sole();
        $this->assertSame($switzerland, $marie->country_id);
        $this->assertSame($switzerland, $marie->phone_country_id);
        $this->assertSame('+41791234567', $marie->phone);
    }

    public function test_the_create_user_command_follows_the_channels(): void
    {
        $this->channels(email: true, phone: false);

        $this->artisan('auth:create-user', [
            '--name' => 'marie', '--first-name' => 'Marie', '--last-name' => 'Curie',
            '--phone' => '079 123 45 67', '--phone-country' => 'CH', '--password' => 'secret', '--locale' => 'fr',
        ])->assertFailed();

        $this->assertFalse(User::query()->where('name', 'marie')->exists());
    }
}
