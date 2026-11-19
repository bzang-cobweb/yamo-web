<?php

namespace Modules\Offer\Tests\Feature\Api;

use App\Models\Setting;
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
use Modules\Offer\Models\OfferInformation;
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
        return Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['street' => 'Rue Njo-Njo 12', 'city' => 'Douala']))->create([
            'title' => ['fr' => 'Appartement 3 pièces à Bonapriso', 'en' => '3-room flat in Bonapriso'],
            'description' => ['fr' => 'Proche des commerces.', 'en' => 'Close to shops.'],
            'status' => 'published',
            'price' => '250000',
            'currency' => 'XAF',
            'category_id' => OfferCategory::query()->where('slug', 'long-term')->value('id'),
            'published_at' => '2026-11-18 10:00:00',
            'details' => ['property_type' => 'apartment', 'furnished' => false, 'surface' => 85, 'rooms' => 3.5, 'bedrooms' => 2, 'available_from' => '2026-12-01', 'charges' => 15000],
            ...$overrides,
        ]);
    }

    public function test_a_visitor_sees_the_detail_described_by_the_server_with_the_full_address(): void
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
        // L'adresse complète, rue comprise, même sans connexion (décision 2026-11-19-fiche-localisation).
        $this->assertSame('Rue Njo-Njo 12, '.trim($offer->address->zip.' Douala').', '.$this->cameroon->getTranslation('name', 'fr'), $data['address']);
        $this->assertNull($data['indication']);
        $this->assertCount(2, $data['photos']);
        $this->assertStringEndsWith('salon.jpg', $data['photos'][0]);
        $this->assertSame('Proche des commerces.', $data['description']);
        $this->assertNull($data['status']);
        // Type, meublé, catégorie en tête ; plus de ville (décisions 2026-11-19-fiche-offre-ordre et 2026-11-19-fiche-sans-ville).
        $this->assertSame([
            ['label' => 'Type de logement', 'value' => 'Appartement'],
            ['label' => 'Meublé', 'value' => 'Non'],
            ['label' => 'Catégorie', 'value' => 'Location longue durée'],
            ['label' => 'Surface', 'value' => '85 m²'],
            ['label' => 'Pièces', 'value' => '3,5'],
            ['label' => 'Chambres', 'value' => '2'],
            ['label' => 'Disponible dès le', 'value' => '1 décembre 2026'],
            // Un montant, avec le symbole de la devise (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
            ['label' => 'Charges mensuelles', 'value' => '15 000 FCFA / mois'],
        ], array_map(fn (array $fact): array => ['label' => $fact['label'], 'value' => preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $fact['value'])], $data['facts']));
        $this->assertArrayNotHasKey('user_id', $data);
    }

    public function test_a_furnished_home_does_not_show_old_charges(): void
    {
        // Décision 2026-11-19-charges-meuble-surface-facultative.
        $offer = $this->home(['details' => ['property_type' => 'studio', 'furnished' => true, 'charges' => 0]]);

        $this->assertNotContains('Charges mensuelles', array_column($this->show($offer)->assertOk()->json('data.facts'), 'label'));
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

    public function test_the_address_line_has_the_zip_and_the_indication_is_served(): void
    {
        $offer = Offer::factory()->at(Address::factory()->for($this->cameroon)->create(['street' => 'Rue du Lac 4', 'zip' => '1200', 'city' => 'Genève']), 'Portail bleu')->create(['status' => 'published']);

        $this->show($offer)->assertOk()
            ->assertJsonPath('data.address', 'Rue du Lac 4, 1200 Genève, '.$this->cameroon->getTranslation('name', 'fr'))
            ->assertJsonPath('data.indication', 'Portail bleu');
        $this->show(Offer::factory()->create(['status' => 'published']))->assertOk()->assertJsonPath('data.address', null);
    }

    public function test_rules_and_information_are_served_apart_from_the_facts(): void
    {
        // Décision 2026-11-19-contrat-regles-informations.
        // Une heure d'arrivée enregistrée avant le retrait du champ n'est plus servie
        // (décision 2026-11-19-arrivee-depart-en-informations).
        // Un âge minimum propre à l'offre, enregistré avant son retrait, non plus
        // (décision 2026-11-19-age-minimum-de-l-app).
        $furnished = $this->home(['details' => ['property_type' => 'studio', 'furnished' => true, 'check_in_from' => '15:00', 'min_age' => 30, 'documents' => ['id_card', 'deposit'], 'pets' => 'no']]);

        $data = $this->show($furnished)->assertOk()->json('data');
        $this->assertSame([
            ['label' => 'Documents demandés', 'value' => "Pièce d'identité, Dépôt de garantie"],
            ['label' => 'Animaux acceptés', 'value' => 'Non'],
        ], $data['rules']);
        $this->assertNotContains('Animaux acceptés', array_column($data['facts'], 'label'));
        $this->assertNotContains('15:00', array_column([...$data['facts'], ...$data['rules']], 'value'));
        $this->assertNotContains('Âge minimum', array_column([...$data['facts'], ...$data['rules']], 'label'));

        // Sans rien de renseigné ni âge minimum réglé : rien.
        $unfurnished = $this->home(['details' => ['property_type' => 'studio', 'furnished' => false]]);
        $this->assertSame([], $this->show($unfurnished)->json('data.rules'));

        // L'âge minimum de l'application, réglé : en tête des règles de toute offre.
        Setting::current()->update(['minimum_age' => 18]);
        $this->assertSame(['label' => 'Âge minimum', 'value' => '18 ans'], $this->show($furnished)->json('data.rules.0'));
        $this->assertSame([['label' => 'Âge minimum', 'value' => '18 ans']], $this->show($this->home(['details' => ['property_type' => 'studio']]))->json('data.rules'));

    }

    public function test_sections_come_from_the_configuration(): void
    {
        // Décision 2026-11-19-sections-fiche-laravel.
        $offer = $this->home(['details' => ['property_type' => 'studio', 'furnished' => true, 'pets' => 'no']]);
        $this->assertSame([
            ['key' => 'location', 'open' => true],
            ['key' => 'facts', 'open' => false],
            ['key' => 'rules', 'open' => false],
            ['key' => 'reviews', 'open' => false],
        ], $this->show($offer)->json('data.sections'));

        // Ordre et ouverture réglés par Laravel ; une section sans contenu n'est pas servie.
        config(['offer.sections' => ['reviews' => true, 'facts' => true, 'location' => false]]);
        $this->assertSame([
            ['key' => 'reviews', 'open' => true],
            ['key' => 'facts', 'open' => true],
            ['key' => 'location', 'open' => false],
        ], $this->show($offer)->json('data.sections'));

        // Aucune ouverte parmi celles servies : la première l'est.
        config(['offer.sections' => ['location' => true, 'facts' => false, 'rules' => false]]);
        $withoutAddress = Offer::factory()->create(['status' => 'published', 'details' => ['property_type' => 'studio', 'pets' => 'no']]);
        $this->assertSame([
            ['key' => 'facts', 'open' => true],
            ['key' => 'rules', 'open' => false],
        ], $this->show($withoutAddress)->json('data.sections'));
    }

    public function test_information_blocks_are_served_in_order_and_language_as_last_sections(): void
    {
        // Décision 2026-11-19-contrat-offer-informations.
        $offer = $this->home();
        OfferInformation::factory()->for($offer)->create(['title' => ['fr' => 'Parking', 'en' => 'Parking'], 'description' => ['fr' => 'Dans la cour.'], 'position' => 1]);
        OfferInformation::factory()->for($offer)->create(['title' => ['fr' => 'Arrivée', 'en' => 'Arrival'], 'description' => ['fr' => "Clés chez le gardien.\nAppeler.", 'en' => 'Keys with the caretaker.'], 'position' => 0]);

        $data = $this->show($offer)->assertOk()->json('data');
        $this->assertSame([['Arrivée', "Clés chez le gardien.\nAppeler."], ['Parking', 'Dans la cour.']], array_map(fn (array $information): array => [$information['title'], $information['description']], $data['informations']));
        $this->assertSame('Arrival', $this->show($offer, 'en')->json('data.informations.0.title'));
        // Après les avis, fermées par défaut.
        $this->assertSame(['key' => 'informations', 'open' => false], end($data['sections']));
        $this->assertSame([], $this->show($this->home())->json('data.informations'));
    }

    public function test_no_private_data_leaks(): void
    {
        $owner = User::factory()->create(['email' => 'proprio@example.test']);
        $offer = $this->home(['user_id' => $owner->getKey()]);
        $offer->address->update(['latitude' => '4.0510564', 'longitude' => '9.7678687']);

        $content = $this->show($offer)->assertOk()->getContent();

        foreach (['proprio@example.test', '4.0510564', '9.7678687', 'user_id', 'latitude'] as $private) {
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
