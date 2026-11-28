<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Mail\PhoneReleasedMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Auth\Tests\Concerns\SignsUpWithCode;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Seul un numéro prouvé par code identifie un compte ou le retient (décision 2026-11-28-numero-non-verifie) : un numéro
 * saisi sans être vérifié ne connecte pas par SMS, ne bloque pas l'unicité, et il est retiré du compte qui le porte dès
 * qu'un autre le prouve.
 */
class UnverifiedPhoneTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;
    use SignsUpWithCode;

    private const PHONE = '+41791234567';

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        config(['auth.phone.inactive_days' => 180]);
        $this->switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    /**
     * Un compte actif qui porte le numéro, vérifié ou non.
     */
    private function holder(bool $verified): User
    {
        return User::factory()->create([
            'email' => 'porteur@example.com',
            'phone' => self::PHONE,
            'phone_country_id' => $this->switzerland->id,
            'phone_verified_at' => $verified ? now() : null,
            'last_seen_at' => now()->subDays(2),
        ]);
    }

    /**
     * @return array{phone: string, phone_country: string}
     */
    private function thePhone(): array
    {
        return ['phone' => self::PHONE, 'phone_country' => 'CH'];
    }

    public function test_an_unverified_phone_does_not_sign_in_by_sms_and_leads_to_registration(): void
    {
        $holder = $this->holder(verified: false);

        $challenge = $this->startWith($this->thePhone());

        $code = VerificationCode::query()->ofChallenge($challenge)->sole();
        $this->assertSame(VerificationPurpose::REGISTER, $code->purpose);
        $this->assertNull($code->user_id);
        $this->verifyCodeFor($challenge, $this->thePhone())->assertOk()->assertJsonPath('mode', 'register');
        $this->assertNull($holder->fresh()->phone_verified_at);
    }

    public function test_registering_by_sms_takes_the_number_from_the_account_that_never_verified_it(): void
    {
        $holder = $this->holder(verified: false);

        $this->signUp($this->thePhone())->assertCreated()->assertJsonPath('data.phone', self::PHONE);

        $owner = User::query()->where('phone', self::PHONE)->sole();
        $this->assertNotSame($holder->id, $owner->id);
        $this->assertNotNull($owner->phone_verified_at);
        $holder->refresh();
        $this->assertNull($holder->phone);
        $this->assertNull($holder->phone_country_id);
        $this->assertSame('porteur@example.com', $holder->email);
        // L'e-mail de prévenance reste réservé au compte inactif.
        Mail::assertNotSent(PhoneReleasedMail::class);
    }

    public function test_a_number_verified_by_another_active_account_is_still_refused(): void
    {
        $holder = $this->holder(verified: true);

        // Par SMS, il mène au compte de son titulaire.
        $challenge = $this->startWith($this->thePhone());
        $this->assertSame(VerificationPurpose::LOGIN, VerificationCode::query()->ofChallenge($challenge)->sole()->purpose);

        // Changer son numéro pour celui-ci : refusé avant tout code.
        $other = User::factory()->create(['email' => 'autre@example.com']);
        $this->withToken($other->createToken('t')->plainTextToken)
            ->postJson('/api/v1/auth/me/phone', $this->thePhone())
            ->assertUnprocessable()->assertJsonValidationErrors('phone');

        // L'autre contact d'une inscription : refusé.
        $this->signUp(['email' => 'nouveau@example.com'], $this->thePhone())->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->assertSame(self::PHONE, $holder->fresh()->phone);
    }

    public function test_the_other_contact_of_a_registration_cannot_take_an_unverified_number_either(): void
    {
        $holder = $this->holder(verified: false);

        // Saisi sans preuve, il ne peut le retirer à personne.
        $this->signUp(['email' => 'nouveau@example.com'], $this->thePhone())->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->assertSame(self::PHONE, $holder->fresh()->phone);
    }

    public function test_changing_to_a_number_another_account_never_verified_takes_it(): void
    {
        $holder = $this->holder(verified: false);
        $user = User::factory()->create(['email' => 'nouveau@example.com']);

        $challenge = $this->withToken($user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/auth/me/phone', $this->thePhone())
            ->assertAccepted()->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::PHONE)])->assertOk();

        $this->assertSame(self::PHONE, $user->refresh()->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($holder->refresh()->phone);
        Mail::assertNotSent(PhoneReleasedMail::class);
    }

    public function test_adding_a_number_another_account_never_verified_takes_it(): void
    {
        $holder = $this->holder(verified: false);
        $user = User::factory()->create(['email' => 'nouveau@example.com', 'phone' => '+41781234567', 'phone_country_id' => $this->switzerland->id, 'phone_verified_at' => now()]);
        $token = $user->createToken('t')->plainTextToken;

        $challenge = $this->withToken($token)->postJson('/api/v1/auth/me/phones', $this->thePhone())->assertAccepted()->json('challenge');
        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::PHONE)])->assertOk();

        $this->assertSame([self::PHONE], $user->phones()->pluck('phone')->all());
        $this->assertNull($holder->refresh()->phone);
    }

    public function test_the_holder_can_still_verify_its_number_until_someone_proves_it(): void
    {
        $holder = $this->holder(verified: false);
        // Un numéro supplémentaire non vérifié ailleurs (ancien principal rangé) ne retient rien.
        $other = User::factory()->create(['email' => 'autre@example.com']);
        $other->phones()->create(['phone' => self::PHONE, 'phone_country_id' => $this->switzerland->id, 'verified_at' => null]);
        $token = $holder->createToken('t')->plainTextToken;

        $challenge = $this->withToken($token)->postJson('/api/v1/auth/me/phone/verify')->assertAccepted()->json('challenge');
        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::PHONE)])->assertOk();

        $this->assertNotNull($holder->refresh()->phone_verified_at);
        $this->assertSame(0, $other->phones()->count());

        // Vérifié, il connecte désormais par SMS.
        $this->travel(61)->seconds();
        $login = $this->startWith($this->thePhone());
        $this->assertSame($holder->id, VerificationCode::query()->ofChallenge($login)->sole()->user_id);
    }
}
