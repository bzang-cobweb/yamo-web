<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Auth\Tests\Concerns\SignsUpWithCode;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * « Se connecter ou s'inscrire » dans un panneau (décision 2026-11-28-connexion-panneau) : identifiant → code → mot de
 * passe → inscription. Contrat `specs/2026-11-28-connexion-panneau.md`, section « API », points 1 à 7.
 */
class SignInPanelApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;
    use SignsUpWithCode;

    private const PASSWORD = 'Le-vrai-mot-2026';

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        config(['auth.phone.inactive_days' => 180]);
        $this->switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(['email' => 'ada@example.com', 'password' => self::PASSWORD, 'locale' => 'fr', ...$attributes]);
    }

    /**
     * Identifiant et code validés : le défi, à l'étape du mot de passe.
     *
     * @param  array<string, string>  $identifier
     */
    private function verifiedChallenge(array $identifier = ['email' => 'ada@example.com'], ?string $mode = null): string
    {
        $challenge = $this->startWith($identifier);
        $response = $this->verifyCodeFor($challenge, $identifier)->assertOk()->assertJsonPath('next', 'password');

        if ($mode !== null) {
            $response->assertJsonPath('mode', $mode);
        }

        return $challenge;
    }

    private function passwordStep(string $challenge, array $data = [])
    {
        return $this->postJson('/api/v1/auth/session', ['challenge' => $challenge, 'device_name' => 'iPhone', ...$data]);
    }

    // 2. POST /auth/login : toujours 202, même forme

    public function test_login_always_answers_202_with_the_same_shape_and_sends_a_code(): void
    {
        $this->member();
        User::factory()->create(['email' => 'ancien@example.com', 'phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id,
            'phone_verified_at' => now()->subYears(2), 'last_seen_at' => now()->subDays(200)]);

        $known = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'ada@example.com'])->assertAccepted();
        $unknown = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'nouvelle@example.com'])->assertAccepted();
        // Compte inactif par SMS (numéro peut-être recyclé) : traité comme inconnu.
        $recycled = $this->postJson('/api/v1/auth/login', ['channel' => 'phone', 'phone' => '+41791234567', 'phone_country' => 'CH'])->assertAccepted();

        foreach ([$known, $unknown, $recycled] as $response) {
            $this->assertSame(['challenge', 'channel', 'destination'], array_keys($response->json()));
        }
        $known->assertJsonPath('channel', 'email')->assertJsonPath('destination', 'a•••@example.com');
        $unknown->assertJsonPath('channel', 'email')->assertJsonPath('destination', 'n•••@example.com');
        $recycled->assertJsonPath('channel', 'sms')->assertJsonPath('destination', '+41 ••• 67');

        $this->assertSame(VerificationPurpose::LOGIN, VerificationCode::query()->ofChallenge($known->json('challenge'))->sole()->purpose);
        $register = VerificationCode::query()->ofChallenge($unknown->json('challenge'))->sole();
        $this->assertSame(VerificationPurpose::REGISTER, $register->purpose);
        $this->assertNull($register->user_id);
        $this->assertSame(['email' => 'nouvelle@example.com'], $register->payload);
        $recycledCode = VerificationCode::query()->ofChallenge($recycled->json('challenge'))->sole();
        $this->assertSame(VerificationPurpose::REGISTER, $recycledCode->purpose);
        $this->assertSame(['phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id], $recycledCode->payload);

        $this->assertNotEmpty($this->emailCode('ada@example.com'));
        $this->assertNotEmpty($this->emailCode('nouvelle@example.com'));
        $this->assertNotEmpty($this->smsCode('+41791234567'));
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_login_refuses_an_invalid_identifier(): void
    {
        $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'pas-un-e-mail'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/auth/login', ['channel' => 'phone', 'phone' => '123', 'phone_country' => 'CH'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame(0, VerificationCode::count());
    }

    // 3. POST /auth/codes/verify : next password, trois modes

    public function test_the_code_leads_to_the_password_step_in_three_modes_and_keeps_the_challenge_open(): void
    {
        $this->member();
        User::factory()->create(['email' => 'sans@example.com', 'password' => null]);

        foreach (['ada@example.com' => 'sign-in', 'sans@example.com' => 'create', 'nouvelle@example.com' => 'register'] as $email => $mode) {
            $challenge = $this->startWith(['email' => $email]);
            $this->verifyCodeFor($challenge, ['email' => $email])
                ->assertOk()
                ->assertExactJson(['next' => 'password', 'challenge' => $challenge, 'mode' => $mode]);

            // Le défi reste ouvert, marqué vérifié.
            $this->assertNotNull(VerificationCode::query()->ofChallenge($challenge)->sole()->verified_at);
        }

        $this->assertSame(0, PersonalAccessToken::count());
        $this->assertSame(2, User::count());
    }

    public function test_the_code_marks_the_proved_identifier_as_verified(): void
    {
        $byEmail = $this->member(['email_verified_at' => null]);
        $byPhone = User::factory()->create(['email' => 'tel@example.com', 'phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id, 'phone_verified_at' => null, 'email_verified_at' => null]);

        $this->verifiedChallenge(['email' => 'ada@example.com'], 'sign-in');
        $this->verifiedChallenge(['phone' => '+41791234567', 'phone_country' => 'CH'], 'register');

        $this->assertNotNull($byEmail->fresh()->email_verified_at);
        // Un numéro jamais vérifié ne trouve pas son compte : le code mène à l'inscription et ne prouve rien pour ce compte
        // (décision 2026-11-28-numero-non-verifie).
        $this->assertNull($byPhone->fresh()->phone_verified_at);
        $this->assertNull($byPhone->fresh()->email_verified_at);
    }

    public function test_an_account_awaiting_deletion_still_leads_to_restore_and_cannot_open_a_session(): void
    {
        $this->member()->delete();

        $challenge = $this->startWith(['email' => 'ada@example.com']);
        $this->verifyCodeFor($challenge, ['email' => 'ada@example.com'])->assertOk()->assertJsonPath('next', 'restore');

        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, PersonalAccessToken::count());
    }

    // 5. POST /auth/session

    public function test_sign_in_with_the_right_password_returns_the_user_and_a_token_once(): void
    {
        $user = $this->member();
        $challenge = $this->verifiedChallenge(mode: 'sign-in');

        $response = $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertOk()->assertJsonPath('data.id', $user->id);
        $this->assertNotEmpty($response->json('token'));
        $this->assertSame(['iPhone'], $user->tokens()->pluck('name')->all());
        $this->assertSame(0, VerificationCode::count());

        // Le défi ne sert qu'une fois.
        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_wrong_password_keeps_the_challenge_until_the_fifth_attempt(): void
    {
        $user = $this->member();
        $challenge = $this->verifiedChallenge(mode: 'sign-in');

        for ($i = 1; $i <= 4; $i++) {
            $this->withHeader('Accept-Language', 'fr')->passwordStep($challenge, ['password' => 'pas-le-bon'])
                ->assertUnprocessable()
                ->assertJsonPath('errors.password.0', 'Mot de passe incorrect.');
        }
        // Le défi tient encore : le bon mot de passe passe.
        $this->assertSame(1, VerificationCode::query()->ofChallenge($challenge)->count());

        $this->passwordStep($challenge, ['password' => 'pas-le-bon'])->assertUnprocessable()->assertJsonValidationErrors('code');
        // Cinquième essai faux : clos (erreur sur `code`, l'app recommence), même le bon mot de passe ne sert plus.
        $this->assertSame(0, VerificationCode::count());
        $this->travel(61)->seconds(); // Hors de la limite de fréquence de la route.
        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_forgotten_password_is_replaced_after_the_code_and_other_devices_are_signed_out(): void
    {
        $user = $this->member();
        $user->createToken('iPad');
        $challenge = $this->verifiedChallenge(mode: 'sign-in');

        $this->passwordStep($challenge, ['password' => 'Nouveau-mot-2026', 'password_confirmation' => 'autre', 'reset' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->passwordStep($challenge, ['password' => 'court', 'password_confirmation' => 'court', 'reset' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->passwordStep($challenge, ['password' => 'Nouveau-mot-2026', 'password_confirmation' => 'Nouveau-mot-2026', 'reset' => true])
            ->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonStructure(['token']);

        $this->assertTrue(Hash::check('Nouveau-mot-2026', $user->fresh()->password));
        $this->assertSame(['iPhone'], $user->tokens()->pluck('name')->all());
    }

    public function test_an_account_without_password_creates_it(): void
    {
        $user = $this->member(['password' => null]);
        $challenge = $this->verifiedChallenge(mode: 'create');

        // Un seul champ ne suffit pas : mot de passe et confirmation.
        $this->passwordStep($challenge, ['password' => 'Nouveau-mot-2026'])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->passwordStep($challenge, ['password' => 'Nouveau-mot-2026', 'password_confirmation' => 'Nouveau-mot-2026'])
            ->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonStructure(['token']);
        $this->assertTrue(Hash::check('Nouveau-mot-2026', $user->fresh()->password));
    }

    public function test_a_new_account_keeps_the_hashed_password_with_the_challenge(): void
    {
        $challenge = $this->verifiedChallenge(['email' => 'nouvelle@example.com'], 'register');

        $this->passwordStep($challenge, ['password' => 'Nouveau-mot-2026', 'password_confirmation' => 'Nouveau-mot-2026'])
            ->assertOk()
            ->assertExactJson(['next' => 'register', 'challenge' => $challenge]);

        $payload = VerificationCode::query()->ofChallenge($challenge)->sole()->payload;
        $this->assertTrue(Hash::check('Nouveau-mot-2026', $payload['password']));
        $this->assertSame(0, User::count());
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_a_challenge_expires_30_minutes_after_its_code(): void
    {
        $this->member();
        $challenge = $this->verifiedChallenge(mode: 'sign-in');

        $this->travel(31)->minutes();

        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_a_challenge_whose_code_is_not_validated_opens_nothing(): void
    {
        $this->member();
        $challenge = $this->startWith(['email' => 'ada@example.com']);

        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->passwordStep('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', ['password' => self::PASSWORD])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/v1/auth/session', [])->assertUnprocessable()->assertJsonValidationErrors(['challenge', 'password']);
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_the_password_step_is_throttled_per_challenge(): void
    {
        $this->member();
        $challenge = $this->verifiedChallenge(mode: 'sign-in');

        for ($i = 0; $i < 5; $i++) {
            $this->passwordStep($challenge, ['password' => 'pas-le-bon'])->assertUnprocessable();
        }

        $this->passwordStep($challenge, ['password' => self::PASSWORD])->assertTooManyRequests();
    }

    // 7. POST /auth/register

    public function test_register_by_email_creates_the_account_with_an_unverified_phone(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')
            ->signUp(['email' => 'nouvelle@example.com'], ['first_name' => 'Zoé', 'last_name' => "d'Arc", 'phone' => '+41791234567', 'phone_country' => 'CH'])
            ->assertCreated();

        $user = User::query()->where('email', 'nouvelle@example.com')->sole();
        $response->assertJsonPath('data.id', $user->id)->assertJsonPath('data.name', 'zoedarc')->assertJsonStructure(['token']);
        $this->assertSame(['Zoé', "d'Arc"], [$user->first_name, $user->last_name]);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('+41791234567', $user->phone);
        $this->assertSame($this->switzerland->id, $user->phone_country_id);
        $this->assertNull($user->phone_verified_at);
        $this->assertSame('en', $user->locale);
        $this->assertSame(['member'], $user->getRoleNames()->all());
        $this->assertTrue(Hash::check($this->newPassword, $user->password));
        $this->assertSame(['iPhone'], $user->tokens()->pluck('name')->all());
        $this->assertSame(0, VerificationCode::count());

        // Ensuite, le compte se connecte avec son mot de passe.
        $this->travel(61)->seconds();
        $challenge = $this->verifiedChallenge(['email' => 'nouvelle@example.com'], 'sign-in');
        $this->passwordStep($challenge, ['password' => $this->newPassword])->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_register_by_phone_takes_an_optional_email(): void
    {
        // Indicatif d'un pays inactif : tous les pays sont proposés aux champs téléphone.
        $france = Country::factory()->create(['code' => 'FR', 'dial_code' => '+33', 'active' => false]);

        $this->signUp(['phone' => '+33612345678', 'phone_country' => 'FR'])->assertCreated()->assertJsonPath('data.email', null);
        $user = User::query()->where('phone', '+33612345678')->sole();
        $this->assertSame($france->id, $user->phone_country_id);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email_verified_at);

        $this->signUp(['phone' => '+41791234567', 'phone_country' => 'CH'], ['email' => 'autre@example.com'])->assertCreated();
        $other = User::query()->where('phone', '+41791234567')->sole();
        $this->assertSame('autre@example.com', $other->email);
        $this->assertNull($other->email_verified_at);
        $this->assertNotNull($other->phone_verified_at);
    }

    public function test_the_generated_pseudonym_is_unique(): void
    {
        User::factory()->create(['name' => 'adamartin']);

        $this->signUp(['email' => 'un@example.com'])->assertCreated()->assertJsonPath('data.name', 'adamartin2');
        $this->signUp(['email' => 'deux@example.com'])->assertCreated()->assertJsonPath('data.name', 'adamartin3');
        $this->signUp(['email' => 'trois@example.com'], ['first_name' => 'Ève', 'last_name' => '!!'])->assertCreated()->assertJsonPath('data.name', 'eve');
    }

    public function test_the_other_contact_must_be_free_and_valid(): void
    {
        User::factory()->create(['email' => 'prise@example.com', 'phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id]);

        $this->signUp(['email' => 'nouvelle@example.com'], ['phone' => '+41791234567', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->signUp(['email' => 'nouvelle2@example.com'], ['phone' => '123', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->signUp(['phone' => '+41781234567', 'phone_country' => 'CH'], ['email' => 'prise@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_register_needs_a_validated_register_challenge_with_its_password(): void
    {
        $data = ['first_name' => 'Ada', 'last_name' => 'Martin'];

        // Code validé, mais pas de mot de passe choisi.
        $challenge = $this->verifiedChallenge(['email' => 'nouvelle@example.com'], 'register');
        $this->postJson('/api/v1/auth/register', ['challenge' => $challenge, ...$data])
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        // Code pas encore validé.
        $pending = $this->startWith(['email' => 'autre@example.com']);
        $this->postJson('/api/v1/auth/register', ['challenge' => $pending, ...$data])->assertUnprocessable()->assertJsonValidationErrors('code');

        // Défi de connexion d'un compte existant.
        $this->member();
        $login = $this->verifiedChallenge(['email' => 'ada@example.com'], 'sign-in');
        $this->postJson('/api/v1/auth/register', ['challenge' => $login, ...$data])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertSame(1, User::count());
    }

    public function test_a_register_challenge_serves_once_and_expires(): void
    {
        $challenge = $this->registerChallenge(['email' => 'nouvelle@example.com']);
        $data = ['challenge' => $challenge, 'first_name' => 'Ada', 'last_name' => 'Martin'];

        $this->travel(31)->minutes();
        $this->postJson('/api/v1/auth/register', $data)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, User::count());

        $this->travelBack();
        $fresh = $this->registerChallenge(['email' => 'autre@example.com']);
        $this->postJson('/api/v1/auth/register', [...$data, 'challenge' => $fresh])->assertCreated();
        $this->postJson('/api/v1/auth/register', [...$data, 'challenge' => $fresh])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(1, User::count());
    }

    public function test_an_identifier_taken_while_the_code_was_pending_is_refused_at_the_code(): void
    {
        $challenge = $this->startWith(['email' => 'nouvelle@example.com']);
        // Pris puis supprimé : la valeur reste réservée jusqu'à la purge.
        User::factory()->create(['email' => 'nouvelle@example.com'])->delete();

        $this->verifyCodeFor($challenge, ['email' => 'nouvelle@example.com'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, VerificationCode::count());
    }

    // 1, 4 et 6. Formulaires servis

    public function test_the_password_forms_are_public_and_translated(): void
    {
        $password = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.session.password')->assertOk();
        $this->assertSame(['password'], array_column($password->json('data.fields'), 'name'));
        $password->assertJsonPath('data.title', 'Votre mot de passe')
            ->assertJsonPath('data.submit.label', 'Se connecter')
            ->assertJsonPath('data.fields.0.type', 'password');

        $new = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.session.new-password')->assertOk();
        $this->assertSame(['password', 'password_confirmation'], array_column($new->json('data.fields'), 'name'));
        $new->assertJsonPath('data.title', 'Choisissez un mot de passe')
            ->assertJsonPath('data.submit.label', 'Continuer');
    }

    public function test_phone_fields_offer_every_country_with_its_dial_code_and_default_to_the_current_one(): void
    {
        Country::factory()->create(['code' => 'FR', 'dial_code' => '+33', 'active' => false]);

        $login = collect($this->getJson('/api/v1/forms/auth.login')->assertOk()->json('data.fields'))->keyBy('name');
        $this->assertSame(['channel', 'email', 'phone'], $login->keys()->all());
        $options = collect($login['phone']['options'])->keyBy('value');
        $this->assertSame(['CH', 'FR'], $options->keys()->sort()->values()->all());
        $this->assertSame('+33', $options['FR']['hint']);
        // Le pays courant (seul pays actif) d'office.
        $this->assertSame(['country' => 'CH', 'number' => null], $login['phone']['default']);

        $register = collect($this->getJson('/api/v1/forms/auth.register?channel=email')->json('data.fields'))->keyBy('name');
        $this->assertSame(['CH', 'FR'], collect($register['phone']['options'])->pluck('value')->sort()->values()->all());
        $this->assertSame(['country' => 'CH', 'number' => null], $register['phone']['default']);
    }
}
