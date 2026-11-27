<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * « Adresse de résidence » dans « Informations personnelles » (décision 2026-11-27-adresse-de-residence) : une seule
 * par compte, une adresse du compte marquée, absente de « Mes adresses ».
 */
class ResidenceApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        // L'espace pays des tests : la Suisse (décision 2026-11-18-espace-pays).
        $this->country = Country::factory()->create(['code' => 'CH', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz', 'currency' => 'CHF']);
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
        return ['street' => 'Rue du Lac 12', 'zip' => '1200', 'region' => (string) $this->regionOf($this->country), 'city' => 'Genève', 'indication' => 'Portail bleu', ...$overrides];
    }

    public function test_the_personal_submenu_links_to_the_residence_after_the_password(): void
    {
        $menu = $this->asUser(User::factory()->create(['locale' => 'fr']))->getJson('/api/v1/auth/me/menu/personal')->assertOk()->json('data');
        $keys = array_column($menu, 'key');
        $link = $menu[array_search('residence', $keys, true)];

        $this->assertSame(array_search('password', $keys, true) + 1, array_search('residence', $keys, true));
        $this->assertSame('Adresse de résidence', $link['label']);
        $this->assertSame('auth.profile.residence', $link['form']);
        $this->assertSame(['ios' => 'house', 'android' => 'home'], $link['icon']);
    }

    public function test_the_form_asks_for_the_address_without_a_recipient(): void
    {
        $fields = $this->asUser(User::factory()->create(['locale' => 'fr']))->getJson('/api/v1/forms/auth.profile.residence')
            ->assertOk()
            ->assertJsonPath('data.title', 'Adresse de résidence')
            ->assertJsonPath('data.submit.method', 'PUT')
            ->assertJsonPath('data.submit.endpoint', '/v1/auth/me/residence')
            ->json('data.fields');

        $this->assertSame(['street', 'zip', 'region', 'city', 'indication'], array_column($fields, 'name'));
        $this->assertNull($fields[0]['default']);
    }

    public function test_the_residence_is_created_then_updated_and_shown_again(): void
    {
        $user = User::factory()->create(['locale' => 'fr', 'first_name' => 'Awa', 'last_name' => 'Diallo']);

        $this->asUser($user)->putJson('/api/v1/auth/me/residence', $this->input())->assertNoContent();
        $this->asUser($user)->putJson('/api/v1/auth/me/residence', $this->input(['street' => 'Rue du Rhône 3', 'indication' => '']))->assertNoContent();

        $this->assertSame(1, $user->addresses()->count());
        $residence = $user->residence()->with('address')->firstOrFail();
        $this->assertTrue($residence->is_residence);
        $this->assertSame('Rue du Rhône 3', $residence->address->street);
        $this->assertNull($residence->indication);
        $this->assertSame('Awa', $residence->first_name);

        $fields = collect($this->asUser($user)->getJson('/api/v1/forms/auth.profile.residence')->json('data.fields'))->keyBy('name');
        $this->assertSame('Rue du Rhône 3', $fields['street']['default']);
        $this->assertSame('1200', $fields['zip']['default']);
        $this->assertSame((string) $this->regionOf($this->country), $fields['region']['default']);
    }

    public function test_the_residence_is_not_in_my_addresses(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $this->asUser($user)->putJson('/api/v1/auth/me/residence', $this->input())->assertNoContent();

        $this->asUser($user)->getJson('/api/v1/lists/auth.addresses')->assertOk()->assertJsonCount(0, 'data.items');
        // Ni ouverte par le formulaire d'une adresse.
        $this->asUser($user)->getJson('/api/v1/forms/auth.address?record='.$user->residence()->value('id'))->assertNotFound();
    }

    public function test_the_address_fields_are_required(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->putJson('/api/v1/auth/me/residence', ['street' => '', 'region' => '999999'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['street', 'zip', 'region', 'city']);
        $this->assertNull($user->residence()->first());
    }

    public function test_requires_authentication(): void
    {
        $this->putJson('/api/v1/auth/me/residence', $this->input())->assertUnauthorized();
    }
}
