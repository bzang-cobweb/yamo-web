<?php

namespace Modules\Auth\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Âge minimum réglé au back-office (décision 2026-11-19-age-minimum) : la date de
 * naissance devient obligatoire et doit donner au moins cet âge, à l'inscription
 * comme dans les informations personnelles. Sans réglage, rien ne change.
 */
class MinimumAgeApiTest extends TestCase
{
    use RefreshDatabase;

    private const IDENTITY = ['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'email' => 'marie@example.com'];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
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

    public function test_without_a_minimum_age_the_birth_date_stays_optional(): void
    {
        $this->getJson('/api/v1/forms/auth.register')
            ->assertJsonPath('data.fields.3.name', 'birth_date')
            ->assertJsonPath('data.fields.3.required', false)
            ->assertJsonPath('data.fields.3.help', null);

        $this->postJson('/api/v1/auth/register', self::IDENTITY)->assertAccepted();
    }

    public function test_with_a_minimum_age_the_birth_date_is_required_and_its_help_says_the_age(): void
    {
        $this->minimumAge(18);

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.register')
            ->assertJsonPath('data.fields.3.name', 'birth_date')
            ->assertJsonPath('data.fields.3.required', true)
            ->assertJsonPath('data.fields.3.help', 'Au moins 18 ans.')
            // Le calendrier s'arrête à la dernière date qui donne 18 ans (décision 2026-11-19-bornes-des-dates).
            ->assertJsonPath('data.fields.3.range', ['min' => '1900-01-01', 'max' => today()->subYears(18)->toDateString(), 'step' => null]);

        $this->postJson('/api/v1/auth/register', self::IDENTITY)->assertJsonValidationErrors('birth_date');
    }

    public function test_registering_too_young_is_refused_and_old_enough_is_accepted(): void
    {
        $this->minimumAge(18);
        $tooYoung = today()->subYears(18)->addDay()->toDateString();
        $exactly = today()->subYears(18)->toDateString();

        $this->withHeader('Accept-Language', 'fr')
            ->postJson('/api/v1/auth/register', [...self::IDENTITY, 'birth_date' => $tooYoung])
            ->assertJsonValidationErrors(['birth_date' => 'Vous devez avoir au moins 18 ans.']);

        $this->postJson('/api/v1/auth/register', [...self::IDENTITY, 'birth_date' => $exactly])->assertAccepted();
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
