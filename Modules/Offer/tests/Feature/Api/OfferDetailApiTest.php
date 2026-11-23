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
use Modules\Offer\Enums\OfferStatusEnum;
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
            // Une vraie catégorie reste possible, à côté du type de location (décision 2026-11-20-type-de-location-garder-categories).
            'category_id' => OfferCategory::query()->firstOrCreate(['slug' => 'standing'], ['name' => ['fr' => 'Standing', 'en' => 'Upscale'], 'position' => 0])->getKey(),
            'published_at' => '2026-11-18 10:00:00',
            'details' => ['property_type' => 'apartment', 'rental_type' => 'long_term', 'surface' => 85, 'rooms' => 3.5, 'bedrooms' => 2, 'bathrooms' => 1, 'available_from' => '2026-12-01', 'charges' => 15000],
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
        // La catégorie en tête ; plus de ville (décisions 2026-11-19-fiche-offre-ordre et 2026-11-19-fiche-sans-ville) ; plus
        // les types de logement et de location, dans le titre composé (décision 2026-11-22-fiche-sections-ordre).
        $this->assertSame([
            ['label' => 'Catégorie', 'value' => 'Standing'],
            ['label' => 'Surface', 'value' => '85 m²'],
            ['label' => 'Pièces', 'value' => '3,5'],
            ['label' => 'Chambres', 'value' => '2'],
            // Après les chambres (décision 2026-11-21-salles-de-bain).
            ['label' => 'Salles de bain', 'value' => '1'],
            ['label' => 'Disponible dès le', 'value' => '1 décembre 2026'],
            // Un montant, avec le symbole de la devise (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
            ['label' => 'Charges mensuelles', 'value' => '15 000 FCFA'],
            // Le loyer mensuel après les charges (décision 2026-11-20-fiche-loyer-caution).
            ['label' => 'Loyer mensuel (sans charges)', 'value' => '250 000 FCFA'],
        ], array_map(fn (array $fact): array => ['label' => $fact['label'], 'value' => preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $fact['value'])], $data['facts']));
        $this->assertArrayNotHasKey('user_id', $data);
    }

    public function test_a_furnished_home_does_not_show_old_charges(): void
    {
        // Décision 2026-11-19-charges-meuble-surface-facultative.
        $offer = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'charges' => 0]]);

        $this->assertNotContains('Charges mensuelles', array_column($this->show($offer)->assertOk()->json('data.facts'), 'label'));
    }

    public function test_the_features_show_the_deposit_and_the_rent_in_advance(): void
    {
        // Décision 2026-11-20-caution-affichee : en mois pour un non meublé, un montant pour un meublé.
        $flat = $this->home(['details' => ['property_type' => 'apartment', 'rental_type' => 'long_term', 'bedrooms' => 2, 'charges' => 15000, 'deposit_months' => 2, 'advance_months' => 3]]);
        $facts = fn (Offer $offer): array => array_map(fn (string $value): string => preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $value), array_column($this->show($offer)->assertOk()->json('data.facts'), 'value', 'label'));

        // Le loyer mensuel après les charges, « (sans charges) » s'il y en a (décision 2026-11-20-fiche-loyer-caution).
        $this->assertSame([
            'Charges mensuelles' => '15 000 FCFA',
            'Loyer mensuel (sans charges)' => '250 000 FCFA',
            'Caution' => '2 mois',
            'Avance de loyer' => '3 mois',
        ], array_slice($facts($flat), -4));

        // Vides ou nuls : ni charges, ni caution, ni avance ; le loyer sans « (sans charges) ».
        $bare = $this->home(['details' => ['property_type' => 'apartment', 'rental_type' => 'shared', 'bedrooms' => 1, 'charges' => 0, 'deposit_months' => 0]]);
        $this->assertSame('250 000 FCFA', $facts($bare)['Loyer mensuel']);
        $this->assertSame([], array_intersect(['Charges mensuelles', 'Caution', 'Avance de loyer', 'Loyer mensuel (sans charges)'], array_keys($facts($bare))));

        // Un meublé : la caution en montant, pas de loyer mensuel.
        $studio = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'bedrooms' => 0, 'deposit' => 100000, 'deposit_months' => 2]]);
        $this->assertSame('100 000 FCFA', $facts($studio)['Caution']);
        $this->assertArrayNotHasKey('Avance de loyer', $facts($studio));
        $this->assertArrayNotHasKey('Loyer mensuel', $facts($studio));
        // Son prix par jour, juste avant sa caution (décision 2026-11-20-fiche-prix-par-jour).
        $this->assertSame(['Prix par jour' => '250 000 FCFA', 'Caution' => '100 000 FCFA'], array_slice($facts($studio), -2));
        // Ni date de disponibilité (décision 2026-11-20-disponibilite-sans-meuble).
        $old = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'bedrooms' => 0, 'available_from' => '2026-12-01']]);
        $this->assertArrayNotHasKey('Disponible dès le', $facts($old));
        // Sans caution : 0 (décision 2026-11-20-caution-meuble-toujours).
        $this->assertSame(['Prix par jour' => '250 000 FCFA', 'Caution' => '0 FCFA'], array_slice($facts($old), -2));
    }

    public function test_texts_follow_the_language(): void
    {
        $data = $this->show($this->home(), 'en')->assertOk()->json('data');

        $this->assertSame('3-room flat in Bonapriso', $data['title']);
        $this->assertSame('Rent', $data['price']['label']);
        $this->assertStringEndsWith(' / month', $data['price']['value']);
        $this->assertNotContains(['label' => 'Rental type', 'value' => 'Rental'], $data['facts']);
        $this->assertSame('Rental · Apartment · '.$data['location'], $data['composed_title']);
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
            // Avec son ton : une pastille sur la photo (décision 2026-11-21-statut-a-la-place-du-coeur).
            ->assertJsonPath('data.status', ['value' => 'draft', 'label' => 'Brouillon', 'tone' => OfferStatusEnum::DRAFT->tone()])
            ->assertJsonPath('data.favorite', null);
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
        // L'heure d'arrivée d'un meublé, parmi les règles (décision 2026-11-20-heures-arrivee-depart).
        // Un âge minimum propre à l'offre, enregistré avant son retrait, non plus
        // (décision 2026-11-19-age-minimum-de-l-app).
        $furnished = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'check_in_from' => '15:00', 'min_age' => 30, 'documents' => ['id_card', 'deposit'], 'pets' => 'no']]);

        $data = $this->show($furnished)->assertOk()->json('data');
        $this->assertSame([
            ['label' => 'Arrivée à partir de', 'value' => '15:00'],
            ['label' => 'Animaux acceptés', 'value' => 'Non'],
            ['label' => 'Documents demandés', 'value' => "Pièce d'identité, Dépôt de garantie"],
        ], $data['rules']);
        $this->assertNotContains('Animaux acceptés', array_column($data['facts'], 'label'));
        $this->assertNotContains('15:00', array_column($data['facts'], 'value'));
        $this->assertNotContains('Âge minimum', array_column([...$data['facts'], ...$data['rules']], 'label'));

        // Sans rien de renseigné ni âge minimum réglé : rien.
        $unfurnished = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'long_term']]);
        $this->assertSame([], $this->show($unfurnished)->json('data.rules'));

        // L'âge minimum de l'application, réglé : en tête des règles de toute offre.
        Setting::current()->update(['minimum_age' => 18]);
        $this->assertSame(['label' => 'Âge minimum', 'value' => '18 ans'], $this->show($furnished)->json('data.rules.0'));
        $this->assertSame([['label' => 'Âge minimum', 'value' => '18 ans']], $this->show($this->home(['details' => ['property_type' => 'studio']]))->json('data.rules'));

    }

    public function test_sections_come_from_the_configuration(): void
    {
        // Décision 2026-11-19-sections-fiche-laravel ; complètes (décision 2026-11-19-sections-construites-laravel).
        $offer = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished', 'pets' => 'no']]);
        $offer->update(['description' => ['fr' => 'Calme et lumineux.']]);
        $sections = $this->show($offer)->json('data.sections');
        // Caractéristiques, description, localisation, puis le reste (décision 2026-11-22-fiche-sections-ordre).
        $this->assertSame([
            ['key' => 'facts', 'type' => 'rows', 'title' => 'Caractéristiques', 'open' => true],
            ['key' => 'description', 'type' => 'text', 'title' => 'Description', 'open' => true],
            ['key' => 'location', 'type' => 'text', 'title' => 'Localisation', 'open' => true],
            ['key' => 'rules', 'type' => 'rows', 'title' => 'Règles', 'open' => false],
            ['key' => 'reviews', 'type' => 'reviews', 'title' => 'Avis', 'open' => false],
        ], $this->outline($sections));
        // Le contenu avec : les lignes, la description, l'adresse et son indication.
        $this->assertSame($this->show($offer)->json('data.facts'), $sections[0]['rows']);
        $this->assertSame('Calme et lumineux.', $sections[1]['text']);
        $this->assertSame($this->show($offer)->json('data.address'), $sections[2]['text']);
        $this->assertSame([['label' => 'Animaux acceptés', 'value' => 'Non']], $sections[3]['rows']);

        // Ordre et ouverture réglés par Laravel ; une section sans contenu n'est pas servie.
        config(['offer.sections' => ['reviews' => true, 'facts' => true, 'location' => false]]);
        $this->assertSame([['reviews', true], ['facts', true], ['location', false]], $this->keysAndOpen($this->show($offer)->json('data.sections')));

        // Aucune ouverte parmi celles servies : la première l'est.
        config(['offer.sections' => ['location' => true, 'facts' => false, 'rules' => false]]);
        $withoutAddress = Offer::factory()->create(['status' => 'published', 'details' => ['property_type' => 'studio', 'surface' => 30, 'pets' => 'no']]);
        $this->assertSame([['facts', true], ['rules', false]], $this->keysAndOpen($this->show($withoutAddress)->json('data.sections')));
    }

    public function test_a_hidden_address_shows_only_the_city_except_to_its_owner(): void
    {
        // Décision 2026-11-21-afficher-adresse.
        $offer = $this->home(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished']]);
        $offer->update(['show_address' => false]);

        // La ville seule, sans indication (décision 2026-11-21-adresse-masquee-ville).
        $data = $this->show($offer)->json('data');
        $this->assertSame($offer->address->city, $data['address']);
        $this->assertNull($data['indication']);
        $this->assertSame($offer->address->city, collect($data['sections'])->firstWhere('key', 'location')['text']);

        app('auth')->forgetGuards();
        $mine = $this->withToken($offer->user->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson("/api/v1/offers/{$offer->getKey()}")->json('data');
        $this->assertStringStartsWith('Rue Njo-Njo 12', (string) $mine['address']);
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function outline(array $sections): array
    {
        return array_map(fn (array $section): array => array_intersect_key($section, array_flip(['key', 'type', 'title', 'open'])), $sections);
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array{0: mixed, 1: mixed}>
     */
    private function keysAndOpen(array $sections): array
    {
        return array_map(fn (array $section): array => [$section['key'], $section['open']], $sections);
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
        // Après les avis, une section `text` par bloc, dans l'ordre, fermées par défaut
        // (décision 2026-11-19-sections-construites-laravel).
        $blocks = array_slice($data['sections'], -2);
        $this->assertSame(['text', 'Arrivée', "Clés chez le gardien.\nAppeler.", false], [$blocks[0]['type'], $blocks[0]['title'], $blocks[0]['text'], $blocks[0]['open']]);
        $this->assertSame('Parking', $blocks[1]['title']);
        $this->assertStringStartsWith('information-', $blocks[0]['key']);
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
