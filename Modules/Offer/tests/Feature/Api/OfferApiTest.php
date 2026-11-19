<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
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
        // L'espace pays des tests : la Suisse, seul pays actif (décision 2026-11-18-espace-pays).
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
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

        return [
            'title' => 'Appartement lumineux',
            'description' => 'Proche du lac.',
            'category_id' => (string) OfferCategory::query()->where('slug', 'furnished')->value('id'),
            'price' => '1850',
            'street' => 'Rue du Lac 4',
            'zip' => '1200',
            'city' => 'Genève',
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

        // « Mes logements », puis « Mes favoris » (décision 2026-11-19-contrat-vedette-favoris).
        $this->assertSame([
            ['key' => 'offers', 'label' => 'Mes logements', 'list' => 'offer.mine'],
            ['key' => 'favorites', 'label' => 'Mes favoris', 'list' => 'offer.favorites'],
        ], array_slice($menu, -2));
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
            ['title', 'description', 'category_id', 'price', 'street', 'zip', 'city', 'status', 'property_type', 'furnished', 'surface', 'rooms', 'bedrooms', 'available_from', 'amenities', 'charges'],
            array_keys($fields),
        );
        $this->assertSame('Loyer', $fields['price']['label']);
        // L'unité suit « Meublé » pendant la saisie.
        $this->assertSame('CHF / mois', $fields['price']['suffix']);
        $this->assertSame(['field' => 'furnished', 'suffixes' => ['true' => 'CHF / jour', 'false' => 'CHF / mois']], $fields['price']['suffix_when']);
        $this->assertSame('Surface', $fields['surface']['label']);
        $this->assertSame('m²', $fields['surface']['suffix']);
        $this->assertSame(['Location longue durée', 'Location meublée', 'Colocation'], array_column($fields['category_id']['options'], 'label'));
        // La rue propose des adresses ; le code postal est facultatif (décision 2026-11-19-contrat-adresse-offre-recherche).
        $this->assertSame('/v1/addresses/suggestions', $fields['street']['suggestions']);
        $this->assertTrue($fields['street']['required']);
        $this->assertFalse($fields['zip']['required']);
        $this->assertSame('draft', $fields['status']['default']);
        // Les charges : un montant mensuel dans la devise du pays.
        $this->assertSame('CHF / mois', $fields['charges']['suffix']);
    }

    public function test_amenities_are_a_multiselect_saved_and_validated(): void
    {
        $user = $this->member();

        // Décision 2026-11-19-contrat-multiselect-intervalle-equipements.
        $fields = array_column($this->asUser($user)->getJson('/api/v1/forms/offer.offer')->json('data.fields'), null, 'name');
        $this->assertSame('multiselect', $fields['amenities']['type']);
        $this->assertCount(13, $fields['amenities']['options']);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['amenities' => ['spa']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amenities.0');
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['amenities' => ['wifi', 'parking']]))->assertNoContent();
        $this->assertSame(['wifi', 'parking'], Offer::query()->sole()->detail('amenities'));
    }

    public function test_a_furnished_home_has_no_charges(): void
    {
        $user = $this->member();

        // Décision 2026-11-19-charges-meuble-surface-facultative.
        $fields = array_column($this->asUser($user)->getJson('/api/v1/forms/offer.offer')->json('data.fields'), null, 'name');
        $this->assertSame(['field' => 'furnished', 'value' => 'false'], $fields['charges']['visible_when']);
        $this->assertFalse($fields['surface']['required']);
        $this->assertFalse($fields['rooms']['required']);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['furnished' => true, 'charges' => '180']))->assertNoContent();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['furnished' => false, 'charges' => '180', 'surface' => null, 'rooms' => null]))->assertNoContent();

        [$furnished, $unfurnished] = Offer::query()->orderBy('id')->get()->all();
        $this->assertNull($furnished->detail('charges'));
        $this->assertSame(180, $unfurnished->detail('charges'));
        $this->assertNull($unfurnished->detail('surface'));
    }

    public function test_the_app_form_comes_in_the_4_steps_of_the_domain(): void
    {
        $user = $this->member();
        UserAddress::factory()->for($user)->create();

        // Décision 2026-11-19-contrat-formulaire-etapes-symbole-devise.
        $steps = $this->asUser($user)->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data.steps');
        // Décision 2026-11-19-etapes-formulaire-ordre-final.
        $this->assertSame(['Le logement', 'Caractéristiques', 'Prix et disponibilité', 'Adresse'], array_column($steps, 'title'));
        $this->assertSame([
            ['property_type', 'furnished', 'category_id', 'title', 'description'],
            ['surface', 'rooms', 'bedrooms', 'amenities'],
            ['price', 'charges', 'available_from', 'status'],
            ['street', 'zip', 'city'],
        ], array_column($steps, 'fields'));

        // « Suivant » : seule l'étape est vérifiée.
        $this->asUser($user)->postJson('/api/v1/forms/offer.offer/steps/1', ['property_type' => 'castle'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_type', 'title'])
            ->assertJsonMissingValidationErrors(['surface', 'rooms', 'price', 'street']);
        // Surface et pièces facultatives (décision 2026-11-19-charges-meuble-surface-facultative).
        $this->asUser($user)->postJson('/api/v1/forms/offer.offer/steps/1', ['property_type' => 'studio', 'title' => 'Studio'])->assertNoContent();
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

    public function test_contact_details_are_allowed_in_an_offer(): void
    {
        $user = $this->member();

        // Le propriétaire paiera pour publier : ses coordonnées sont permises (décision 2026-11-19-modele-proprietaire-payant).
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['title' => 'Studio, appelez le 699 12 34 56', 'description' => 'Plus de photos sur WhatsApp']))->assertNoContent();
        $this->assertSame('Studio, appelez le 699 12 34 56', Offer::query()->sole()->getTranslation('title', 'fr'));
    }

    public function test_an_existing_address_is_reused_otherwise_created_never_in_the_account(): void
    {
        $user = $this->member();
        $existing = Address::query()->create(['country_id' => Country::query()->where('code', 'CH')->value('id'), 'street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']);

        // Décision 2026-11-19-contrat-adresse-offre-recherche : même empreinte (casse, espaces), même adresse.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['street' => '  rue du lac 4 ', 'city' => 'GENÈVE']))->assertNoContent();
        $this->assertSame($existing->getKey(), Offer::query()->latest('id')->value('address_id'));

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['street' => 'Chemin Neuf 1', 'zip' => '', 'city' => 'Nyon']))->assertNoContent();
        $created = Address::query()->whereKey(Offer::query()->latest('id')->value('address_id'))->sole();
        $this->assertSame(['Chemin Neuf 1', '', 'Nyon'], [$created->street, $created->zip, $created->city]);
        $this->assertSame(2, Address::query()->count());
        // Le carnet du compte n'est ni lu ni rempli.
        $this->assertSame(0, $user->addresses()->count());

        // Rue et ville obligatoires.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['street' => '', 'city' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['street', 'city']);
    }

    public function test_street_suggestions_serve_only_street_zip_and_city_of_the_country(): void
    {
        $user = $this->member();
        $switzerland = Country::query()->where('code', 'CH')->value('id');
        foreach (['Rue du Lac 4', 'Rue du Lac 10', 'Avenue de la Gare'] as $street) {
            Address::query()->create(['country_id' => $switzerland, 'street' => $street, 'zip' => '1200', 'city' => 'Genève']);
        }
        Address::query()->create(['country_id' => Country::factory()->create(['code' => 'FR'])->getKey(), 'street' => 'Rue du Lac 99', 'zip' => '75000', 'city' => 'Paris']);

        $this->getJson('/api/v1/addresses/suggestions?q=lac')->assertUnauthorized();
        $this->assertSame([], $this->asUser($user)->withHeader('X-Country', 'CH')->getJson('/api/v1/addresses/suggestions?q=la')->assertOk()->json('data'));

        $suggestions = $this->asUser($user)->withHeader('X-Country', 'CH')->getJson('/api/v1/addresses/suggestions?q=du lac')->assertOk()->json('data');
        $this->assertSame(['Rue du Lac 10', 'Rue du Lac 4'], array_column($suggestions, 'value'));
        $this->assertSame(['value' => 'Rue du Lac 4', 'label' => 'Rue du Lac 4 — 1200 Genève', 'fill' => ['zip' => '1200', 'city' => 'Genève']], $suggestions[1]);

        // Au plus 10.
        foreach (range(1, 12) as $number) {
            Address::query()->create(['country_id' => $switzerland, 'street' => "Chemin des Pins {$number}", 'zip' => '1260', 'city' => 'Nyon']);
        }
        $this->assertCount(10, $this->asUser($user)->withHeader('X-Country', 'CH')->getJson('/api/v1/addresses/suggestions?q=pins')->json('data'));
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
        // Statut, détails et prix en gras ; le titre saisi en sous-titre s'il diffère.
        $this->assertStringStartsWith('Publiée · ', $data[0]['highlight']);
        $this->assertStringContainsString('900', $data[0]['highlight']);
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
        // L'adresse de l'offre, préremplie.
        $this->assertSame(['Rue du Lac 4', '1200', 'Genève'], [$fields['street'], $fields['zip'], $fields['city']]);

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
