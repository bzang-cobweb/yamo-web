<?php

namespace Modules\Auth\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Auth\Tests\Concerns\SignsUpWithCode;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Âge minimum réglé au back-office (décision 2026-11-19-age-minimum) : la date de
 * naissance devient obligatoire et doit donner au moins cet âge, à l'inscription
 * comme dans les informations personnelles. Sans réglage, rien ne change.
 */
class MinimumAgeApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;
    use SignsUpWithCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function minimumAge(?int $age): void
    {
        Setting::current()->update(['minimum_age' => $age]);
    }

    private function asUser(User $user): static
    {
        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_without_a_minimum_age_the_birth_date_is_not_asked_at_registration(): void
    {
        // La date de naissance reste au profil (décision 2026-11-28-connexion-panneau).
        $this->assertSame(['first_name', 'last_name'], array_column($this->getJson('/api/v1/forms/auth.register')->json('data.fields'), 'name'));

        $this->signUp(['email' => 'ada@example.com'])->assertCreated()->assertJsonPath('data.birth_date', null);
    }

    public function test_with_a_minimum_age_the_birth_date_is_required_and_its_help_says_the_age(): void
    {
        $this->minimumAge(18);

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.register')
            ->assertJsonPath('data.fields.2.name', 'birth_date')
            ->assertJsonPath('data.fields.2.required', true)
            ->assertJsonPath('data.fields.2.help', 'Au moins 18 ans.')
            // Le calendrier s'arrête à la dernière date qui donne 18 ans (décision 2026-11-19-bornes-des-dates).
            ->assertJsonPath('data.fields.2.range', ['min' => '1900-01-01', 'max' => today()->subYears(18)->toDateString(), 'step' => null]);

        $this->signUp(['email' => 'ada@example.com'])->assertJsonValidationErrors('birth_date');
    }

    public function test_registering_too_young_is_refused_and_old_enough_is_accepted(): void
    {
        $this->minimumAge(18);
        $tooYoung = today()->subYears(18)->addDay()->toDateString();
        $exactly = today()->subYears(18)->toDateString();

        $challenge = $this->registerChallenge(['email' => 'ada@example.com']);
        $register = fn (string $birthDate) => $this->withHeader('Accept-Language', 'fr')
            ->postJson('/api/v1/auth/register', ['challenge' => $challenge, 'first_name' => 'Ada', 'last_name' => 'Martin', 'birth_date' => $birthDate]);

        $register($tooYoung)->assertJsonValidationErrors(['birth_date' => 'Vous devez avoir au moins 18 ans.']);
        $register($exactly)->assertCreated()->assertJsonPath('data.birth_date', $exactly);
    }

    public function test_personal_information_needs_a_birth_date_old_enough(): void
    {
        $this->minimumAge(21);
        $user = User::factory()->create(['birth_date' => null, 'locale' => 'fr']);
        $data = ['first_name' => 'Jeanne', 'last_name' => 'Dupont'];

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', $data)->assertJsonValidationErrors('birth_date');
        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$data, 'birth_date' => today()->subYears(20)->toDateString()])
            ->assertJsonValidationErrors(['birth_date' => 'Vous devez avoir au moins 21 ans.']);
        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$data, 'birth_date' => '1990-05-04'])->assertOk();

        $this->assertSame('1990-05-04', $user->fresh()->birth_date->format('Y-m-d'));
    }

    public function test_the_back_office_saves_the_minimum_age(): void
    {
        $this->minimumAge(16);

        $this->assertSame(16, Setting::minimumAge());

        $this->minimumAge(null);

        $this->assertNull(Setting::minimumAge());
    }
}
