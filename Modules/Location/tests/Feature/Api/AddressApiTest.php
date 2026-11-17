<?php

namespace Modules\Location\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\UserAddress;
use Tests\TestCase;

/**
 * « Mes adresses » dans l'app : la liste, le formulaire (vide ou lié à une
 * `UserAddress`), les envois qu'ils décrivent et les suggestions de la rue
 * (décisions 2026-11-18-adresses-liste-generique et
 * 2026-11-18-adresse-du-compte-modele).
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
        return [
            'gender' => 'female', 'first_name' => 'Jeanne', 'last_name' => 'Dupont',
            'country_id' => (string) $country->getKey(), 'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève',
            ...$overrides,
        ];
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

    public function test_lists_only_the_account_addresses_with_recipient_edit_and_remove(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($this->switzerland())->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $mine = UserAddress::factory()->for($user)->for($address)->create(['first_name' => 'Jeanne', 'last_name' => 'Dupont']);
        UserAddress::factory()->for($address)->create();

        $items = $this->asUser($user)->getJson('/api/v1/lists/location.addresses')->assertOk()->json('data.items');

        $this->assertSame([[
            'id' => (string) $mine->getKey(),
            'title' => 'Jeanne Dupont',
            'subtitle' => 'Rue du Lac 4, 1200 Genève, Suisse',
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

    public function test_the_empty_form_starts_with_the_account_as_recipient_and_suggests_streets(): void
    {
        $user = User::factory()->create(['gender' => 'male', 'first_name' => 'Paul', 'last_name' => 'Martin']);

        $data = $this->asUser($user)
            ->getJson('/api/v1/forms/location.address')
            ->assertOk()
            ->assertJsonPath('data.title', 'Ajouter une adresse')
            ->assertJsonPath('data.submit', [
                'label' => 'Enregistrer', 'method' => 'POST', 'endpoint' => '/v1/auth/me/addresses',
                'destructive' => false, 'success' => ['action' => 'back', 'message' => 'Adresse enregistrée.'],
            ])
            ->json('data.fields');

        $fields = array_column($data, null, 'name');
        $this->assertSame(['gender', 'first_name', 'last_name', 'country_id', 'street', 'zip', 'city', 'latitude', 'longitude'], array_keys($fields));
        $this->assertSame(['male', 'Paul', 'Martin', null], [$fields['gender']['default'], $fields['first_name']['default'], $fields['last_name']['default'], $fields['street']['default']]);
        $this->assertSame(['endpoint' => '/v1/addresses/suggestions', 'params' => ['country_id'], 'min_length' => 3], $fields['street']['suggestions']);
        $this->assertNull($fields['zip']['suggestions']);
    }

    public function test_the_form_for_one_of_its_addresses_is_prefilled_and_sent_to_its_route(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($this->switzerland())->create(['street' => 'Rue du Lac 4', 'latitude' => '46.2043907']);
        $entry = UserAddress::factory()->for($user)->for($address)->create(['gender' => 'female', 'first_name' => 'Léa', 'last_name' => 'Roux']);

        $definition = $this->asUser($user)
            ->getJson("/api/v1/forms/location.address?record={$entry->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.title', "Modifier l'adresse")
            ->assertJsonPath('data.submit.method', 'PATCH')
            ->assertJsonPath('data.submit.endpoint', "/v1/auth/me/addresses/{$entry->getKey()}")
            ->json('data.fields');

        $defaults = array_column($definition, 'default', 'name');
        $this->assertSame(['female', 'Léa', 'Roux'], [$defaults['gender'], $defaults['first_name'], $defaults['last_name']]);
        $this->assertSame((string) $address->country_id, $defaults['country_id']);
        $this->assertSame('Rue du Lac 4', $defaults['street']);
        $this->assertSame('46.2043907', $defaults['latitude']);
    }

    public function test_the_form_for_another_account_address_is_404(): void
    {
        $entry = UserAddress::factory()->create();

        $this->asUser(User::factory()->create())
            ->getJson("/api/v1/forms/location.address?record={$entry->getKey()}")
            ->assertNotFound();
    }

    public function test_other_forms_ignore_record(): void
    {
        $this->asUser(User::factory()->create())
            ->getJson('/api/v1/forms/auth.profile.preferences?record=12')
            ->assertOk();
    }

    public function test_adding_a_new_address_creates_it_with_its_recipient(): void
    {
        $user = User::factory()->create();

        $this->asUser($user)->postJson('/api/v1/auth/me/addresses', $this->input($this->switzerland(), ['latitude' => '46.2', 'longitude' => '6.1']))
            ->assertNoContent();

        $entry = $user->addresses()->sole();
        $this->assertSame('Jeanne Dupont', $entry->recipient());
        $this->assertSame('Rue du Lac 4', $entry->address->street);
        $this->assertSame('46.2000000', $entry->address->latitude);
    }

    public function test_adding_an_existing_address_reuses_it(): void
    {
        $country = $this->switzerland();
        $existing = Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $user = User::factory()->create();

        $this->asUser($user)->postJson('/api/v1/auth/me/addresses', $this->input($country, ['street' => 'rue du lac 4']))
            ->assertNoContent();

        $this->assertSame(1, Address::query()->count());
        $this->assertTrue($user->addresses()->sole()->address->is($existing));
    }

    public function test_invalid_input_is_reported_under_the_fields(): void
    {
        $this->asUser(User::factory()->create())
            ->postJson('/api/v1/auth/me/addresses', ['country_id' => '999', 'street' => '', 'zip' => '1200', 'city' => 'Genève', 'latitude' => '91', 'gender' => 'other'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender', 'first_name', 'last_name', 'country_id', 'street', 'latitude']);
    }

    public function test_changing_an_address_leaves_the_shared_one_intact(): void
    {
        $country = $this->switzerland();
        $user = User::factory()->create();
        $shared = Address::factory()->for($country)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);
        $entry = UserAddress::factory()->for($user)->for($shared)->create();
        $partner = UserAddress::factory()->for($shared)->create();

        $this->asUser($user)->patchJson("/api/v1/auth/me/addresses/{$entry->getKey()}", $this->input($country, ['street' => 'Rue du Rhône 10', 'first_name' => 'Léa']))
            ->assertNoContent();

        $this->assertSame('Rue du Lac 4', $shared->fresh()->street);
        $this->assertSame('Rue du Rhône 10', $entry->fresh()->address->street);
        $this->assertSame('Léa', $entry->fresh()->first_name);
        $this->assertTrue($partner->fresh()->address->is($shared));
    }

    public function test_removing_an_address_only_deletes_the_entry(): void
    {
        $user = User::factory()->create();
        $entry = UserAddress::factory()->for($user)->create();

        $this->asUser($user)->deleteJson("/api/v1/auth/me/addresses/{$entry->getKey()}")->assertNoContent();

        $this->assertSame(0, $user->addresses()->count());
        $this->assertNotNull($entry->address->fresh());
    }

    public function test_another_account_address_cannot_be_changed_or_removed(): void
    {
        $entry = UserAddress::factory()->create();
        $intruder = User::factory()->create();

        $this->asUser($intruder)->patchJson("/api/v1/auth/me/addresses/{$entry->getKey()}", $this->input($entry->address->country))->assertNotFound();
        $this->asUser($intruder)->deleteJson("/api/v1/auth/me/addresses/{$entry->getKey()}")->assertNotFound();

        $this->assertNotNull($entry->fresh());
        $this->assertSame(0, $intruder->addresses()->count());
    }

    public function test_suggestions_give_at_most_five_addresses_of_the_country_without_coordinates(): void
    {
        $country = $this->switzerland();
        foreach (range(1, 6) as $number) {
            Address::factory()->for($country)->create(['street' => "Rue du Lac {$number}", 'zip' => '1200', 'city' => 'Genève', 'latitude' => '46.2']);
        }
        Address::factory()->create(['street' => 'Rue du Lac 1']);

        $data = $this->asUser(User::factory()->create())
            ->getJson("/api/v1/addresses/suggestions?q=du%20lac&country_id={$country->getKey()}")
            ->assertOk()
            ->json('data');

        $this->assertCount(5, $data);
        $this->assertSame([
            'label' => 'Rue du Lac 1 — 1200 Genève',
            'values' => ['country_id' => (string) $country->getKey(), 'street' => 'Rue du Lac 1', 'zip' => '1200', 'city' => 'Genève'],
        ], $data[0]);
    }

    public function test_suggestions_need_an_account_and_three_characters(): void
    {
        $this->getJson('/api/v1/addresses/suggestions?q=rue')->assertUnauthorized();
        $this->asUser(User::factory()->create())->getJson('/api/v1/addresses/suggestions?q=ru')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }
}
