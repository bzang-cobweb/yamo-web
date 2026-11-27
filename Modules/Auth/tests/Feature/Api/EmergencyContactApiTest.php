<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\EmergencyContact;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * « Contact d'urgence » dans « Informations personnelles » (décision 2026-11-27-contact-d-urgence) : nom, téléphone
 * et langue obligatoires, lien de parenté et e-mail facultatifs ; un seul par compte, créé puis modifié.
 */
class EmergencyContactApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create(['code' => 'CH', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz', 'dial_code' => '+41']);
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    /**
     * @return array<string, string>
     */
    private function input(array $overrides = []): array
    {
        return ['name' => 'Mariam Diallo', 'relationship' => 'Mère', 'email' => 'mariam@example.com', 'phone' => '079 123 45 67', 'phone_country' => 'CH', 'locale' => 'en', ...$overrides];
    }

    public function test_the_personal_submenu_links_to_the_emergency_contact_after_the_residence(): void
    {
        $menu = $this->asUser(User::factory()->create(['locale' => 'fr']))->getJson('/api/v1/auth/me/menu/personal')->assertOk()->json('data');
        $keys = array_column($menu, 'key');
        $link = $menu[array_search('emergency-contact', $keys, true)];

        $this->assertSame(array_search('residence', $keys, true) + 1, array_search('emergency-contact', $keys, true));
        $this->assertSame("Contact d'urgence", $link['label']);
        $this->assertSame('auth.profile.emergency-contact', $link['form']);
        $this->assertSame(['ios' => 'cross.case', 'android' => 'emergency'], $link['icon']);
    }

    public function test_the_form_describes_its_fields_and_defaults(): void
    {
        $fields = collect($this->asUser(User::factory()->create(['locale' => 'en']))->getJson('/api/v1/forms/auth.profile.emergency-contact')
            ->assertOk()
            ->assertJsonPath('data.submit.method', 'PUT')
            ->assertJsonPath('data.submit.endpoint', '/v1/auth/me/emergency-contact')
            ->json('data.fields'))->keyBy('name');

        $this->assertSame(['name', 'relationship', 'email', 'phone', 'locale'], $fields->keys()->all());
        $this->assertSame([true, false, false, true, true], $fields->pluck('required')->all());
        $this->assertSame('phone', $fields['phone']['type']);
        $this->assertSame(['country' => 'CH', 'number' => null], $fields['phone']['default']);
        // La langue du compte d'office.
        $this->assertSame('en', $fields['locale']['default']);
    }

    public function test_the_contact_is_created_then_updated_and_shown_again(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->putJson('/api/v1/auth/me/emergency-contact', $this->input())->assertNoContent();
        $this->asUser($user)->putJson('/api/v1/auth/me/emergency-contact', $this->input(['email' => '', 'relationship' => '', 'locale' => 'fr']))->assertNoContent();

        $this->assertSame(1, EmergencyContact::query()->count());
        $contact = $user->emergencyContact()->firstOrFail();
        $this->assertSame('Mariam Diallo', $contact->name);
        $this->assertNull($contact->email);
        $this->assertNull($contact->relationship);
        $this->assertSame('+41791234567', $contact->phone);
        $this->assertSame($this->country->getKey(), $contact->phone_country_id);
        $this->assertSame('fr', $contact->locale);

        $fields = collect($this->asUser($user)->getJson('/api/v1/forms/auth.profile.emergency-contact')->json('data.fields'))->keyBy('name');
        $this->assertSame('Mariam Diallo', $fields['name']['default']);
        $this->assertSame('CH', $fields['phone']['default']['country']);
        $this->assertSame('079 123 45 67', $fields['phone']['default']['number']);
        $this->assertSame('fr', $fields['locale']['default']);
    }

    public function test_name_phone_and_language_are_required_and_checked(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->putJson('/api/v1/auth/me/emergency-contact', ['email' => 'pas-un-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'phone_country', 'locale', 'email']);
        $this->asUser($user)->putJson('/api/v1/auth/me/emergency-contact', $this->input(['phone' => '12', 'locale' => 'xx']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'locale']);
        $this->assertNull($user->emergencyContact()->first());
    }

    public function test_requires_authentication(): void
    {
        $this->putJson('/api/v1/auth/me/emergency-contact', $this->input())->assertUnauthorized();
    }
}
