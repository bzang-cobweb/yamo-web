<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * Offres d'un membre dans l'app, domaine « location » (décision
 * 2026-11-18-offres-et-domaines) : menu, liste, formulaire, envois.
 */
class OfferApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    /**
     * @return array<string, mixed>
     */
    private function input(User $user, array $overrides = []): array
    {
        $address = UserAddress::factory()->for($user)->create()->address_id;

        return [
            'title' => 'Appartement lumineux',
            'description' => 'Proche du lac.',
            'category_id' => (string) OfferCategory::query()->where('slug', 'furnished')->value('id'),
            'price' => '1850',
            'address_id' => (string) $address,
            'status' => 'published',
            'property_type' => 'apartment',
            'furnished' => true,
            'surface' => '64',
            'rooms' => '3.5',
            'bedrooms' => '2',
            'available_from' => '2026-12-01',
            'charges' => '180',
            ...$overrides,
        ];
    }

    public function test_the_menu_links_to_my_homes(): void
    {
        $menu = $this->asUser($this->member())->getJson('/api/v1/auth/me/menu')->assertOk()->json('data');

        $this->assertSame(['key' => 'offers', 'label' => 'Mes logements', 'list' => 'offer.mine'], end($menu));
    }

    public function test_the_form_has_the_common_fields_then_the_domain_attributes(): void
    {
        $user = $this->member();
        UserAddress::factory()->for($user)->create();

        $data = $this->asUser($user)->getJson('/api/v1/forms/offer.offer')->assertOk()
            ->assertJsonPath('data.title', 'Ajouter : Logement')
            ->assertJsonPath('data.submit.endpoint', '/v1/offers')
            ->json('data.fields');

        $fields = array_column($data, null, 'name');
        $this->assertSame(
            ['title', 'description', 'category_id', 'price', 'address_id', 'status', 'property_type', 'furnished', 'surface', 'rooms', 'bedrooms', 'available_from', 'charges'],
            array_keys($fields),
        );
        $this->assertSame('Loyer mensuel (CHF)', $fields['price']['label']);
        $this->assertSame('Surface (m²)', $fields['surface']['label']);
        $this->assertSame(['Location longue durée', 'Location meublée', 'Colocation'], array_column($fields['category_id']['options'], 'label'));
        $this->assertCount(1, $fields['address_id']['options']);
        $this->assertSame('draft', $fields['status']['default']);
    }

    public function test_a_member_adds_a_home_with_its_attributes(): void
    {
        $user = $this->member();

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user))->assertNoContent();

        $offer = Offer::query()->sole();
        $this->assertTrue($offer->user->is($user));
        $this->assertSame(OfferStatusEnum::PUBLISHED, $offer->status);
        $this->assertNotNull($offer->published_at);
        $this->assertSame('1850.00', $offer->price);
        $this->assertSame('CHF', $offer->currency);
        $this->assertSame('apartment', $offer->detail('property_type'));
        $this->assertTrue($offer->detail('furnished'));
        $this->assertSame(3.5, $offer->detail('rooms'));
        $this->assertSame(64, $offer->detail('surface'));
    }

    public function test_attributes_are_validated_by_the_domain_schema(): void
    {
        $user = $this->member();

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['surface' => '-3', 'property_type' => 'castle', 'price' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['surface', 'property_type', 'price']);
    }

    public function test_the_address_must_be_one_of_the_account(): void
    {
        $user = $this->member();
        $foreign = UserAddress::factory()->create()->address_id;

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['address_id' => (string) $foreign]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_an_account_without_the_member_role_cannot_add(): void
    {
        $user = User::factory()->create();

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user))->assertForbidden();
        $this->asUser($user)->getJson('/api/v1/lists/offer.mine')->assertOk()->assertJsonPath('data.create', null);
    }

    public function test_the_list_shows_my_offers_only_with_status_and_price(): void
    {
        $user = $this->member();
        $mine = Offer::factory()->for($user)->create(['title' => 'Studio', 'status' => 'published', 'price' => '900', 'currency' => 'CHF']);
        Offer::factory()->create(['title' => 'Autre']);

        $data = $this->asUser($user)->getJson('/api/v1/lists/offer.mine')->assertOk()
            ->assertJsonPath('data.title', 'Mes logements')
            ->assertJsonPath('data.create', ['label' => 'Ajouter un logement', 'form' => 'offer.offer'])
            ->assertJsonPath('data.search', 'Rechercher par titre…')
            ->json('data.items');

        $this->assertCount(1, $data);
        $this->assertSame((string) $mine->getKey(), $data[0]['id']);
        $this->assertSame('Studio', $data[0]['title']);
        $this->assertStringStartsWith('Publiée · ', $data[0]['subtitle']);
        $this->assertStringContainsString('900', $data[0]['subtitle']);
        $this->assertSame("/v1/offers/{$mine->getKey()}", $data[0]['delete']['endpoint']);
    }

    public function test_editing_prefills_and_updates_my_offer(): void
    {
        $user = $this->member();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user))->assertNoContent();
        $offer = Offer::query()->sole();

        $fields = array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->assertOk()
            ->assertJsonPath('data.submit.method', 'PATCH')
            ->json('data.fields'), 'default', 'name');
        $this->assertSame('Appartement lumineux', $fields['title']);
        $this->assertSame('apartment', $fields['property_type']);

        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", $this->input($user, ['title' => 'Grand 3,5 pièces', 'surface' => '70']))->assertNoContent();

        $this->assertSame('Grand 3,5 pièces', $offer->fresh()->title);
        $this->assertSame(70, $offer->fresh()->detail('surface'));
    }

    public function test_another_account_offer_is_404_and_removing_is_soft(): void
    {
        $user = $this->member();
        $other = Offer::factory()->create();
        $mine = Offer::factory()->for($user)->create();

        $this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$other->getKey()}")->assertNotFound();
        $this->asUser($user)->patchJson("/api/v1/offers/{$other->getKey()}", $this->input($user))->assertNotFound();
        $this->asUser($user)->deleteJson("/api/v1/offers/{$other->getKey()}")->assertNotFound();

        $this->asUser($user)->deleteJson("/api/v1/offers/{$mine->getKey()}")->assertNoContent();
        $this->assertSoftDeleted($mine);
        $this->assertNotNull($other->fresh());
    }

    public function test_searching_my_offers_by_title(): void
    {
        $user = $this->member();
        Offer::factory()->for($user)->create(['title' => 'Studio au centre']);
        $loft = Offer::factory()->for($user)->create(['title' => 'Loft 100% rénové']);

        $items = $this->asUser($user)->getJson('/api/v1/lists/offer.mine?search=100%25')->assertOk()->json('data.items');

        $this->assertSame([(string) $loft->getKey()], array_column($items, 'id'));
    }
}
