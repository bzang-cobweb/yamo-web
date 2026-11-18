<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * La vitrine de l'accueil (décision 2026-11-18-accueil-offres-du-pays) : offres
 * publiées du pays courant, ouvertes sans connexion.
 */
class OfferBrowseApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
    }

    private function browse(string $query = '', string $country = 'CM', string $locale = 'fr')
    {
        return $this->withHeaders(['Accept-Language' => $locale, 'X-Country' => $country])->getJson('/api/v1/lists/offer.browse'.$query);
    }

    public function test_a_visitor_sees_the_published_offers_of_the_country_newest_first(): void
    {
        $douala = Address::factory()->for($this->cameroon)->create(['city' => 'Douala']);
        $older = Offer::factory()->create(['title' => ['fr' => 'Studio à Akwa', 'en' => 'Studio in Akwa'], 'status' => 'published', 'price' => '25000', 'currency' => 'XAF', 'address_id' => $douala->getKey()]);
        $newer = Offer::factory()->create(['title' => ['fr' => 'Maison à Bastos'], 'status' => 'published']);
        Offer::factory()->create(['status' => 'draft']);
        Offer::factory()->create(['status' => 'archived']);
        $senegal = Country::factory()->create(['code' => 'SN']);
        Offer::factory()->create(['status' => 'published', 'country_id' => $senegal->getKey()]);

        $data = $this->browse()->assertOk()
            ->assertJsonPath('data.title', 'Logements')
            ->assertJsonPath('data.create', null)
            ->assertJsonPath('data.search', 'Rechercher par titre…')
            ->json('data');

        $this->assertSame([(string) $newer->getKey(), (string) $older->getKey()], array_column($data['items'], 'id'));
        $item = $data['items'][1];
        $this->assertSame('Studio à Akwa', $item['title']);
        $this->assertSame('25 000 FCFA · Douala', preg_replace('/[\x{00A0}\x{202F}]/u', ' ', (string) $item['subtitle']));
        $this->assertNull($item['edit']);
        $this->assertNull($item['delete']);

        // En anglais, et par recherche dans toutes les langues.
        $this->assertSame('Studio in Akwa', $this->browse('', 'CM', 'en')->json('data.items.1.title'));
        $this->assertSame([(string) $older->getKey()], array_column($this->browse('?search=akwa')->json('data.items'), 'id'));
    }

    public function test_pages_of_20_without_any_private_field(): void
    {
        Offer::factory()->count(26)->create(['status' => 'published']);

        $first = $this->browse()->assertOk()->json('data');
        $second = $this->browse('?cursor='.urlencode($first['next_cursor']))->assertOk()->json('data');

        $this->assertSame([20, 6], [count($first['items']), count($second['items'])]);
        $this->assertNull($second['next_cursor']);
        $this->assertSame(['id', 'title', 'subtitle', 'image', 'edit', 'delete'], array_keys($first['items'][0]));
    }

    public function test_an_offer_shows_its_first_photo_and_none_without(): void
    {
        Storage::fake('public');
        $with = Offer::factory()->create(['status' => 'published']);
        $with->addMedia(UploadedFile::fake()->image('salon.jpg', 800, 600))->toMediaCollection(Offer::PHOTOS);
        $with->addMedia(UploadedFile::fake()->image('cuisine.jpg', 800, 600))->toMediaCollection(Offer::PHOTOS);
        $without = Offer::factory()->create(['status' => 'published']);

        $items = array_column($this->browse()->assertOk()->json('data.items'), 'image', 'id');

        $this->assertStringEndsWith('salon.jpg', (string) $items[(string) $with->getKey()]);
        $this->assertNull($items[(string) $without->getKey()]);
    }

    public function test_a_member_sees_the_same_list(): void
    {
        Offer::factory()->create(['status' => 'published']);
        $user = User::factory()->create();

        $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('X-Country', 'CM')
            ->getJson('/api/v1/lists/offer.browse')->assertOk()->assertJsonCount(1, 'data.items');
    }

    public function test_other_lists_stay_closed_to_visitors_and_no_domain_means_no_list(): void
    {
        $this->withHeader('X-Country', 'CM')->getJson('/api/v1/lists/offer.mine')->assertUnauthorized();

        config(['offer.domain' => null]);
        $this->browse()->assertNotFound();
    }

    public function test_without_a_country_and_several_active_it_answers_422(): void
    {
        Country::factory()->create(['code' => 'SN']);

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/lists/offer.browse')->assertUnprocessable();
    }
}
