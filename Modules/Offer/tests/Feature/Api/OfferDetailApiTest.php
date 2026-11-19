<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * Fiche d'une offre (décision 2026-11-18-fiche-offre) : ouverte sans connexion,
 * décrite par le serveur, sans la rue.
 */
class OfferDetailApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    private function show(Offer $offer, string $locale = 'fr', string $country = 'CM')
    {
        app('auth')->forgetGuards();

        return $this->withHeaders(['Accept-Language' => $locale, 'X-Country' => $country])->getJson("/api/v1/offers/{$offer->getKey()}");
    }

    private function home(array $overrides = []): Offer
    {
        return Offer::factory()->create([
            'title' => ['fr' => 'Appartement 3 pièces à Bonapriso', 'en' => '3-room flat in Bonapriso'],
            'description' => ['fr' => 'Proche des commerces.', 'en' => 'Close to shops.'],
            'status' => 'published',
            'price' => '250000',
            'currency' => 'XAF',
            'address_id' => Address::factory()->for($this->cameroon)->create(['street' => 'Rue Njo-Njo 12', 'city' => 'Douala'])->getKey(),
            'category_id' => OfferCategory::query()->where('slug', 'long-term')->value('id'),
            'published_at' => '2026-11-18 10:00:00',
            'details' => ['property_type' => 'apartment', 'furnished' => false, 'surface' => 85, 'rooms' => 3.5, 'bedrooms' => 2, 'available_from' => '2026-12-01', 'charges' => 15000],
            ...$overrides,
        ]);
    }

    public function test_a_visitor_sees_the_detail_described_by_the_server_without_the_street(): void
    {
        Storage::fake('public');
        $offer = $this->home();
        $offer->addMedia(UploadedFile::fake()->image('salon.jpg'))->toMediaCollection(Offer::PHOTOS);
        $offer->addMedia(UploadedFile::fake()->image('cuisine.jpg'))->toMediaCollection(Offer::PHOTOS);

        $response = $this->show($offer)->assertOk();
        $data = $response->json('data');

        $this->assertSame('Appartement 3 pièces à Bonapriso', $data['title']);
        $this->assertSame('Loyer', $data['price']['label']);
        $this->assertSame('250 000 FCFA / mois', preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $data['price']['value']));
        $this->assertSame('Douala', $data['location']);
        $this->assertCount(2, $data['photos']);
        $this->assertStringEndsWith('salon.jpg', $data['photos'][0]);
        $this->assertSame('Proche des commerces.', $data['description']);
        $this->assertNull($data['status']);
        $this->assertSame([
            ['label' => 'Catégorie', 'value' => 'Location longue durée'],
            ['label' => 'Type de logement', 'value' => 'Appartement'],
            ['label' => 'Meublé', 'value' => 'Non'],
            ['label' => 'Surface', 'value' => '85 m²'],
            ['label' => 'Pièces', 'value' => '3,5'],
            ['label' => 'Chambres', 'value' => '2'],
            ['label' => 'Disponible dès le', 'value' => '1 décembre 2026'],
            // Un montant, avec le symbole de la devise (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
            ['label' => 'Charges mensuelles', 'value' => '15 000 FCFA / mois'],
        ], array_map(fn (array $fact): array => ['label' => $fact['label'], 'value' => preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $fact['value'])], $data['facts']));
        // La rue n'est jamais servie.
        $this->assertStringNotContainsString('Njo-Njo', $response->getContent());
        $this->assertArrayNotHasKey('user_id', $data);
    }

    public function test_texts_follow_the_language(): void
    {
        $data = $this->show($this->home(), 'en')->assertOk()->json('data');

        $this->assertSame('3-room flat in Bonapriso', $data['title']);
        $this->assertSame('Rent', $data['price']['label']);
        $this->assertStringEndsWith(' / month', $data['price']['value']);
        $this->assertContains(['label' => 'Furnished', 'value' => 'No'], $data['facts']);
        $this->assertContains(['label' => 'Area', 'value' => '85 m²'], $data['facts']);
    }

    public function test_drafts_archived_removed_and_other_countries_are_404_for_a_visitor(): void
    {
        $senegal = Country::factory()->create(['code' => 'SN']);

        $this->show($this->home(['status' => 'draft']))->assertNotFound();
        $this->show($this->home(['status' => 'archived']))->assertNotFound();
        $removed = $this->home();
        $removed->delete();
        $this->show($removed)->assertNotFound();
        $this->show($this->home(['country_id' => $senegal->getKey()]))->assertNotFound();
    }

    public function test_the_owner_sees_their_draft_with_its_status(): void
    {
        $owner = User::factory()->create(['locale' => 'fr']);
        $draft = $this->home(['status' => 'draft', 'user_id' => $owner->getKey()]);

        $this->withToken($owner->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])
            ->getJson("/api/v1/offers/{$draft->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.status', ['value' => 'draft', 'label' => 'Brouillon']);
    }

    public function test_another_member_does_not_see_a_draft_and_the_owner_sees_an_archived_one(): void
    {
        $owner = User::factory()->create(['locale' => 'fr']);
        $draft = $this->home(['status' => 'draft', 'user_id' => $owner->getKey()]);
        $archived = $this->home(['status' => 'archived', 'user_id' => $owner->getKey()]);
        $stranger = User::factory()->create();

        $this->withToken($stranger->createToken('iPhone')->plainTextToken)->withHeader('X-Country', 'CM')
            ->getJson("/api/v1/offers/{$draft->getKey()}")->assertNotFound();
        app('auth')->forgetGuards();
        $this->withToken($owner->createToken('iPhone')->plainTextToken)->withHeader('X-Country', 'CM')
            ->getJson("/api/v1/offers/{$archived->getKey()}")->assertOk()->assertJsonPath('data.status.value', 'archived');
    }

    public function test_no_private_data_leaks(): void
    {
        $owner = User::factory()->create(['email' => 'proprio@example.test']);
        $offer = $this->home(['user_id' => $owner->getKey()]);
        $offer->address->update(['latitude' => '4.0510564', 'longitude' => '9.7678687']);

        $content = $this->show($offer)->assertOk()->getContent();

        foreach (['proprio@example.test', '4.0510564', '9.7678687', 'Njo-Njo', 'user_id', 'latitude'] as $private) {
            $this->assertStringNotContainsString($private, $content);
        }
    }

    public function test_another_domain_serves_its_own_facts_without_touching_the_core(): void
    {
        app(DomainRegistry::class)->register(DetailShopDomain::class);
        config(['offer.domain' => 'test.detail-shop']);
        $shirt = Offer::factory()->create(['title' => ['fr' => 'T-shirt'], 'status' => 'published', 'details' => ['size' => 'M', 'stock' => 5]]);

        $this->assertSame(
            [['label' => 'Taille', 'value' => 'M'], ['label' => 'Stock', 'value' => '5']],
            $this->show($shirt)->assertOk()->json('data.facts'),
        );
    }
}

/** Domaine de test : une boutique, ses propres caractéristiques. */
class DetailShopDomain extends Domain
{
    public static function key(): string
    {
        return 'test.detail-shop';
    }

    public function label(): string
    {
        return 'Article';
    }

    public function pluralLabel(): string
    {
        return 'Articles';
    }

    public function mineLabel(): string
    {
        return 'Mes articles';
    }

    public function attributes(): array
    {
        return [
            Field::select('size')->label('Taille')->options(['S' => 'S', 'M' => 'M']),
            Field::number('stock')->label('Stock'),
        ];
    }
}
