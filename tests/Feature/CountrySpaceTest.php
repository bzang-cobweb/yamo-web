<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Support\Currencies;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * Espace pays (décision 2026-11-18-espace-pays) : pays proposés à l'entrée, pays
 * courant par `X-Country`, offres et adresses rangées par pays, devise du pays.
 */
class CountrySpaceTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    private Country $senegal;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'name' => ['fr' => 'Cameroun', 'en' => 'Cameroon'], 'native_name' => 'Cameroun', 'currency' => 'XAF']);
        $this->senegal = Country::factory()->create(['code' => 'SN', 'name' => ['fr' => 'Sénégal', 'en' => 'Senegal'], 'native_name' => 'Sénégal', 'currency' => 'XOF']);
        Country::factory()->inactive()->create(['code' => 'CH']);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function in(User $user, ?string $country): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)
            ->withHeaders(['Accept-Language' => 'fr', 'X-Country' => (string) $country]);
    }

    public function test_the_entry_list_shows_active_countries_with_flag_and_currency_without_login(): void
    {
        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/countries')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['code' => 'CM', 'name' => 'Cameroun', 'native_name' => 'Cameroun', 'flag' => '🇨🇲', 'currency' => 'XAF', 'dial_code' => $this->cameroon->dial_code],
                ['code' => 'SN', 'name' => 'Sénégal', 'native_name' => 'Sénégal', 'flag' => '🇸🇳', 'currency' => 'XOF', 'dial_code' => $this->senegal->dial_code],
            ]]);
    }

    public function test_without_a_country_and_several_active_what_depends_on_it_answers_422(): void
    {
        $user = $this->member();

        $this->in($user, null)->getJson('/api/v1/lists/offer.mine')->assertUnprocessable()->assertJsonValidationErrors(['country']);
        $this->in($user, 'CH')->getJson('/api/v1/lists/auth.addresses')->assertUnprocessable();
        // Ce qui n'en dépend pas reste servi.
        $this->in($user, null)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_a_single_active_country_is_the_space_without_header(): void
    {
        $this->senegal->update(['active' => false]);
        $user = $this->member();

        $this->in($user, null)->getJson('/api/v1/lists/offer.mine')->assertOk();
    }

    public function test_offers_live_in_their_country_with_its_currency(): void
    {
        $user = $this->member();
        $address = UserAddress::factory()->for($user)->for(Address::factory()->for($this->cameroon))->create()->address_id;
        UserAddress::factory()->for($user)->for(Address::factory()->for($this->senegal))->create();

        $fields = array_column($this->in($user, 'CM')->getJson('/api/v1/forms/offer.offer')->json('data.fields'), null, 'name');
        $this->assertSame('Loyer', $fields['price']['label']);
        // Le symbole local de la devise, pas son code (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
        $this->assertSame('FCFA / mois', $fields['price']['suffix']);
        $this->assertSame([(string) $address], array_column($fields['address_id']['options'], 'value'));

        $this->in($user, 'CM')->postJson('/api/v1/offers', [
            'title' => 'Studio à Bonanza', 'status' => 'published', 'price' => '85000', 'address_id' => (string) $address,
            'property_type' => 'studio', 'surface' => '25', 'rooms' => '1',
        ])->assertNoContent();

        $offer = Offer::query()->sole();
        $this->assertTrue($offer->country->is($this->cameroon));
        $this->assertSame('XAF', $offer->currency);
        // Format français, sans décimales ; espaces insécables ramenés à des espaces.
        // Statut et prix en gras (décision 2026-11-19-item-titre-saisi-details).
        $highlight = preg_replace('/[\x{00A0}\x{202F}]/u', ' ', (string) $this->in($user, 'CM')->getJson('/api/v1/lists/offer.mine')->json('data.items.0.highlight'));
        $this->assertSame('Publiée · 85 000 FCFA / mois', $highlight);

        // Au Sénégal : un autre espace, sans cette offre, qui y répond 404.
        $this->assertSame([], $this->in($user, 'SN')->getJson('/api/v1/lists/offer.mine')->json('data.items'));
        $this->in($user, 'SN')->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->assertNotFound();
        $this->in($user, 'SN')->deleteJson("/api/v1/offers/{$offer->getKey()}")->assertNotFound();
        $this->assertNotSoftDeleted($offer);
    }

    public function test_account_addresses_live_in_their_country(): void
    {
        $user = $this->member();
        $douala = UserAddress::factory()->for($user)->for(Address::factory()->for($this->cameroon))->create();
        $dakar = UserAddress::factory()->for($user)->for(Address::factory()->for($this->senegal))->create();

        $this->assertSame([(string) $douala->getKey()], array_column($this->in($user, 'CM')->getJson('/api/v1/lists/auth.addresses')->json('data.items'), 'id'));
        $this->in($user, 'CM')->getJson("/api/v1/forms/auth.address?record={$dakar->getKey()}")->assertNotFound();
        $this->in($user, 'CM')->deleteJson("/api/v1/auth/me/addresses/{$dakar->getKey()}")->assertNotFound();

        $country = collect($this->in($user, 'CM')->getJson('/api/v1/forms/auth.address')->json('data.fields'))->firstWhere('name', 'country_id');
        $this->assertTrue($country['locked']);
        $this->assertSame((string) $this->cameroon->getKey(), $country['default']);
        // Une adresse d'un autre pays est refusée dans cet espace.
        $this->in($user, 'CM')->postJson('/api/v1/auth/me/addresses', [
            'gender' => 'female', 'first_name' => 'Awa', 'last_name' => 'Diop',
            'country_id' => (string) $this->senegal->getKey(), 'street' => 'Rue 10', 'zip' => '1000', 'city' => 'Dakar',
        ])->assertUnprocessable()->assertJsonValidationErrors(['country_id']);
    }

    public function test_the_phone_country_defaults_to_the_current_country(): void
    {
        $phone = collect($this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'SN'])->getJson('/api/v1/forms/auth.register')->json('data.fields'))->firstWhere('name', 'phone');

        $this->assertSame('SN', $phone['default']['country']);
    }

    public function test_existing_offers_get_the_country_of_their_address(): void
    {
        $migration = require module_path('Offer', 'database/migrations/2026_11_24_110000_add_country_to_offers.php');
        $migration->down();
        $user = User::factory()->create();
        $address = Address::factory()->for($this->senegal)->create();
        DB::table('offers')->insert([
            ['user_id' => $user->getKey(), 'address_id' => $address->getKey(), 'title' => '{"fr":"A"}', 'status' => 'draft', 'details' => '[]'],
            ['user_id' => $user->getKey(), 'address_id' => null, 'title' => '{"fr":"B"}', 'status' => 'draft', 'details' => '[]'],
        ]);

        $migration->up();

        // Avec l'adresse : son pays ; sans adresse et plusieurs pays actifs : aucun.
        $this->assertSame([$this->senegal->getKey(), null], DB::table('offers')->orderBy('id')->pluck('country_id')->map(fn ($id) => $id === null ? null : (int) $id)->all());
    }

    public function test_currencies_come_from_icu(): void
    {
        $this->assertSame(['XAF', 'XOF', 'CHF', 'EUR'], array_map(fn (string $code): ?string => Currencies::forCountry($code), ['CM', 'SN', 'CH', 'FR']));
    }
}
