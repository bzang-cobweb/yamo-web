<?php

namespace Modules\Location\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * « Mes adresses » dans l'app : la liste, le formulaire (vide ou lié à une
 * adresse) et les envois qu'ils décrivent (décision
 * 2026-11-18-adresses-liste-generique).
 */
class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(User $user): static
    {
        // Les textes suivent la langue du compte.
        $user->forceFill(['locale' => 'fr'])->save();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    private function switzerland(): Country
    {
        return Country::factory()->create(['code' => 'CH', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz']);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Country $country, array $overrides = []): array
    {
        return ['country_id' => (string) $country->getKey(), 'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève', ...$overrides];
    }

    public function test_the_list_requires_authentication_and_unknown_lists_are_404(): void
    {
        $this->getJson('/api/v1/lists/location.addresses')->assertUnauthorized();
        $this->asUser(User::factory()->create())->getJson('/api/v1/lists/nothing.here')->assertNotFound();
    }

    public function test_an_empty_list_describes_its_title_text_and_create_button(): void
    {
        $this->asUser(User::factory()->create())
            ->getJson('/api/v1/lists/location.addresses')
            ->assertOk()
            ->assertExactJson(['data' => [
                'name' => 'location.addresses',
                'title' => 'Mes adresses',
                'empty' => "Aucune adresse pour l'instant.",
                'create' => ['label' => 'Ajouter une adresse', 'form' => 'location.address'],
                'items' => [],
            ]]);
    }

    public function test_lists_only_the_account_addresses_with_edit_and_remove(): void
    {
        $user = User::factory()->create();
        $mine = Address::factory()->for($this->switzerland())->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $user->addresses()->attach($mine);
        Address::factory()->create();

        $items = $this->asUser($user)->getJson('/api/v1/lists/location.addresses')->assertOk()->json('data.items');

        $this->assertSame([[
            'id' => (string) $mine->getKey(),
            'title' => 'Rue du Lac 4',
            'subtitle' => '1200 Genève, Suisse',
            'edit' => ['form' => 'location.address', 'record' => (string) $mine->getKey()],
            'delete' => [
                'label' => 'Retirer',
                'confirm' => 'Retirer cette adresse ?',
                'method' => 'DELETE',
                'endpoint' => "/v1/auth/me/addresses/{$mine->getKey()}",
                'destructive' => true,
                'success' => ['action' => 'back', 'message' => 'Adresse retirée.'],
            ],
        ]], $items);
    }

    public function test_the_empty_form_is_sent_to_the_creation_route(): void
    {
        $this->asUser(User::factory()->create())
            ->getJson('/api/v1/forms/location.address')
            ->assertOk()
            ->assertJsonPath('data.title', 'Ajouter une adresse')
            ->assertJsonPath('data.fields.1.default', null)
            ->assertJsonPath('data.submit', [
                'label' => 'Enregistrer', 'method' => 'POST', 'endpoint' => '/v1/auth/me/addresses',
                'destructive' => false, 'success' => ['action' => 'back', 'message' => 'Adresse enregistrée.'],
            ]);
    }

    public function test_the_form_for_one_of_its_addresses_is_prefilled_and_sent_to_its_route(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($this->switzerland())->create(['street' => 'Rue du Lac 4', 'latitude' => '46.2043907']);
        $user->addresses()->attach($address);

        $definition = $this->asUser($user)
            ->getJson("/api/v1/forms/location.address?record={$address->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.title', "Modifier l'adresse")
            ->assertJsonPath('data.submit.method', 'PATCH')
            ->assertJsonPath('data.submit.endpoint', "/v1/auth/me/addresses/{$address->getKey()}")
            ->json('data.fields');

        $defaults = array_column($definition, 'default', 'name');
        $this->assertSame((string) $address->country_id, $defaults['country_id']);
        $this->assertSame('Rue du Lac 4', $defaults['street']);
        $this->assertSame('46.2043907', $defaults['latitude']);
    }

    public function test_the_form_for_another_account_address_is_404(): void
    {
        $address = Address::factory()->create();
        User::factory()->create()->addresses()->attach($address);

        $this->asUser(User::factory()->create())
            ->getJson("/api/v1/forms/location.address?record={$address->getKey()}")
            ->assertNotFound();
    }

    public function test_other_forms_ignore_record(): void
    {
        $this->asUser(User::factory()->create())
            ->getJson('/api/v1/forms/auth.profile.preferences?record=12')
            ->assertOk();
    }

    public function test_adding_a_new_address_creates_and_attaches_it(): void
    {
        $user = User::factory()->create();
        $country = $this->switzerland();

        $this->asUser($user)->postJson('/api/v1/auth/me/addresses', $this->input($country, ['latitude' => '46.2', 'longitude' => '6.1']))
            ->assertNoContent();

        $address = $user->addresses()->sole();
        $this->assertSame('Rue du Lac 4', $address->street);
        $this->assertSame('46.2000000', $address->latitude);
    }

    public function test_adding_an_existing_address_only_attaches_it(): void
    {
        $country = $this->switzerland();
        $existing = Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $user = User::factory()->create();

        $this->asUser($user)->postJson('/api/v1/auth/me/addresses', $this->input($country, ['street' => 'rue du lac 4']))
            ->assertNoContent();

        $this->assertSame(1, Address::query()->count());
        $this->assertTrue($user->addresses()->sole()->is($existing));
    }

    public function test_invalid_input_is_reported_under_the_fields(): void
    {
        $this->asUser(User::factory()->create())
            ->postJson('/api/v1/auth/me/addresses', ['country_id' => '999', 'street' => '', 'zip' => '1200', 'city' => 'Genève', 'latitude' => '91'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['country_id', 'street', 'latitude']);
    }

    public function test_changing_an_address_moves_the_account_and_leaves_the_shared_one(): void
    {
        $country = $this->switzerland();
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $shared = Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $user->addresses()->attach($shared);
        $partner->addresses()->attach($shared);

        $this->asUser($user)->patchJson("/api/v1/auth/me/addresses/{$shared->getKey()}", $this->input($country, ['street' => 'Rue du Rhône 10']))
            ->assertNoContent();

        $this->assertSame('Rue du Lac 4', $shared->fresh()->street);
        $this->assertSame('Rue du Rhône 10', $user->addresses()->sole()->street);
        $this->assertTrue($partner->addresses()->sole()->is($shared));
    }

    public function test_removing_an_address_only_detaches_it(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address);

        $this->asUser($user)->deleteJson("/api/v1/auth/me/addresses/{$address->getKey()}")->assertNoContent();

        $this->assertSame(0, $user->addresses()->count());
        $this->assertNotNull($address->fresh());
    }

    public function test_another_account_address_cannot_be_changed_or_removed(): void
    {
        $address = Address::factory()->create();
        $owner = User::factory()->create();
        $owner->addresses()->attach($address);
        $intruder = User::factory()->create();

        $this->asUser($intruder)->patchJson("/api/v1/auth/me/addresses/{$address->getKey()}", $this->input($address->country))->assertNotFound();
        $this->asUser($intruder)->deleteJson("/api/v1/auth/me/addresses/{$address->getKey()}")->assertNotFound();

        $this->assertTrue($owner->addresses()->sole()->is($address));
        $this->assertSame(0, $intruder->addresses()->count());
    }
}
