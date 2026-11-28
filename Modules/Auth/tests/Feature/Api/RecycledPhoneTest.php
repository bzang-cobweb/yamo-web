<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Mail\PhoneReleasedMail;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Numéro recyclé par un opérateur : son nouveau titulaire n'entre jamais dans
 * l'ancien compte, et récupère l'usage de son numéro si l'ancien compte est
 * inactif (décision 2026-11-20-restauration-numero-recycle).
 */
class RecycledPhoneTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        config(['auth.phone.inactive_days' => 180]);
        $this->switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function oldOwner(int $daysSinceLastSeen, array $attributes = []): User
    {
        return User::factory()->create([
            'email' => 'ancien@example.com',
            'phone' => '+41791234567',
            'phone_country_id' => $this->switzerland->id,
            'phone_verified_at' => now()->subYears(2),
            'last_seen_at' => now()->subDays($daysSinceLastSeen),
            'password' => 'secret-password',
            'locale' => 'en',
            ...$attributes,
        ]);
    }

    private function forgotByPhone()
    {
        return $this->postJson('/api/v1/auth/password/forgot', ['channel' => 'phone', 'phone' => '079 123 45 67', 'phone_country' => 'CH']);
    }

    /**
     * Le numéro saisi pour se connecter ou s'inscrire (décision 2026-11-28-connexion-panneau) : le défi, et la réponse
     * au code SMS.
     *
     * @return array{0: string, 1: TestResponse}
     */
    private function codeForThePhone(): array
    {
        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'phone', 'phone' => '+41791234567', 'phone_country' => 'CH'])
            ->assertAccepted()->json('challenge');

        return [$challenge, $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode('+41791234567')])];
    }

    /**
     * Le nouveau titulaire du numéro s'inscrit : le code, le mot de passe, puis l'inscription.
     */
    private function registerWithThePhone(): TestResponse
    {
        [$challenge, $verified] = $this->codeForThePhone();
        $verified->assertOk()->assertJsonPath('mode', 'register');
        $this->postJson('/api/v1/auth/session', ['challenge' => $challenge, 'password' => 'Le-bon-mot-2026', 'password_confirmation' => 'Le-bon-mot-2026'])
            ->assertOk()->assertJsonPath('next', 'register');

        return $this->postJson('/api/v1/auth/register', ['challenge' => $challenge, 'first_name' => 'Nina', 'last_name' => 'Titulaire', 'device_name' => 'iPhone']);
    }

    public function test_an_inactive_account_cannot_reset_its_password_by_sms(): void
    {
        $this->oldOwner(daysSinceLastSeen: 200);

        // Même réponse qu'un compte inconnu, et aucun SMS ne part.
        $this->forgotByPhone()->assertAccepted()->assertJsonPath('channel', 'sms');
        $this->assertSame([], $this->sms->messages);

        // L'e-mail, lui, reste un moyen de récupérer le compte.
        $this->postJson('/api/v1/auth/password/forgot', ['channel' => 'email', 'email' => 'ancien@example.com'])->assertAccepted();
        $this->assertNotEmpty($this->emailCode('ancien@example.com'));
    }

    public function test_an_active_account_still_resets_by_sms(): void
    {
        $this->oldOwner(daysSinceLastSeen: 10);

        $this->forgotByPhone()->assertAccepted();
        $this->assertNotEmpty($this->smsCode('+41791234567'));
    }

    public function test_an_account_never_seen_counts_from_its_creation(): void
    {
        $this->travelTo(now()->subDays(200), fn () => $this->oldOwner(daysSinceLastSeen: 0, attributes: ['last_seen_at' => null]));

        $this->forgotByPhone()->assertAccepted();
        $this->assertSame([], $this->sms->messages);
    }

    public function test_the_delay_comes_from_the_config(): void
    {
        config(['auth.phone.inactive_days' => 30]);
        $this->oldOwner(daysSinceLastSeen: 40);

        $this->forgotByPhone()->assertAccepted();
        $this->assertSame([], $this->sms->messages);
    }

    public function test_the_new_owner_signs_up_and_the_number_leaves_the_inactive_account(): void
    {
        $old = $this->oldOwner(daysSinceLastSeen: 200);

        $this->registerWithThePhone()
            ->assertCreated()
            ->assertJsonPath('data.phone', '+41791234567');

        $old->refresh();
        $this->assertNull($old->phone);
        $this->assertNull($old->phone_verified_at);
        $this->assertSame('ancien@example.com', $old->email);

        // L'ancien titulaire en est prévenu, dans la langue de son compte, numéro masqué.
        Mail::assertSent(PhoneReleasedMail::class, fn (PhoneReleasedMail $mail): bool => $mail->hasTo('ancien@example.com')
            && $mail->maskedPhone === '+41 ••• 67'
            && $mail->locale === 'en');
        $rendered = (new PhoneReleasedMail('+41 ••• 67'))->locale('fr')->render();
        $this->assertStringContainsString('Le numéro +41 ••• 67 a été retiré de votre compte yamo.', $rendered);
    }

    public function test_an_old_account_without_email_is_not_notified(): void
    {
        $this->oldOwner(daysSinceLastSeen: 200, attributes: ['email' => null]);

        $this->registerWithThePhone()->assertCreated();

        Mail::assertNotSent(PhoneReleasedMail::class);
    }

    public function test_an_active_holder_keeps_the_number(): void
    {
        $holder = $this->oldOwner(daysSinceLastSeen: 10);

        // Le numéro mène au compte de son titulaire, pas à une inscription.
        $this->codeForThePhone()[1]->assertOk()->assertJsonPath('mode', 'sign-in');
        $this->assertSame('+41791234567', $holder->refresh()->phone);
    }

    public function test_an_account_awaiting_deletion_keeps_the_number_until_the_purge(): void
    {
        $this->oldOwner(daysSinceLastSeen: 200)->delete();

        // Inactif, il ne se connecte plus par SMS ; supprimé, il garde le numéro : l'inscription est refusée dès le code.
        $this->codeForThePhone()[1]->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_adding_the_number_to_an_account_releases_it_too(): void
    {
        $old = $this->oldOwner(daysSinceLastSeen: 200);
        $newcomer = User::factory()->create(['phone' => null]);

        $challenge = $this->withToken($newcomer->createToken('t')->plainTextToken)
            ->postJson('/api/v1/auth/me/phone', ['phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertAccepted()->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode('+41791234567')])->assertOk();

        $this->assertSame('+41791234567', $newcomer->refresh()->phone);
        $this->assertNull($old->refresh()->phone);
    }

    public function test_two_new_owners_at_once_end_in_a_clean_refusal_and_nothing_half_done(): void
    {
        $old = $this->oldOwner(daysSinceLastSeen: 200);
        // Course : au moment précis de l'enregistrement, une autre demande a pris le numéro.
        User::creating(function (User $user): void {
            if ($user->last_name === 'Titulaire') {
                DB::table('users')->insert([
                    'name' => 'concurrent', 'first_name' => 'C', 'last_name' => 'C', 'locale' => 'fr', 'password' => 'x',
                    'phone' => '+41791234567', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->registerWithThePhone()
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        // Tout ou rien : ni nouveau compte, ni numéro retiré de l'ancien, ni e-mail.
        $this->assertFalse(User::query()->where('last_name', 'Titulaire')->exists());
        $this->assertSame('+41791234567', $old->refresh()->phone);
        Mail::assertNotSent(PhoneReleasedMail::class);
    }

    public function test_the_new_owner_cannot_sign_in_to_the_old_account(): void
    {
        // Le code SMS ouvrirait l'ancien compte au nouveau titulaire : un compte inactif ne se connecte plus par SMS, le
        // numéro mène à l'inscription (qui le reprend, voir plus haut), avec la même réponse qu'un numéro inconnu.
        $old = $this->oldOwner(daysSinceLastSeen: 200);

        [$challenge, $verified] = $this->codeForThePhone();
        $verified->assertOk()->assertExactJson(['next' => 'password', 'challenge' => $challenge, 'mode' => 'register']);
        $this->assertSame(0, $old->tokens()->count());
    }

    public function test_an_inactive_account_still_signs_in_by_email(): void
    {
        $old = $this->oldOwner(daysSinceLastSeen: 200);

        $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => $old->email])
            ->assertAccepted()
            ->assertJsonPath('channel', 'email');
    }
}
