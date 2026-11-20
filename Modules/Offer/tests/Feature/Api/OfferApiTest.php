<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Form\Models\FormUpload;
use Modules\Form\Tests\Concerns\MakesFormUploads;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;
use Modules\Offer\Models\OfferInformation;
use Tests\TestCase;

/**
 * Offres d'un membre dans l'app, domaine « location » (décision
 * 2026-11-18-offres-et-domaines) : menu, liste, formulaire, envois.
 */
class OfferApiTest extends TestCase
{
    use MakesFormUploads, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        // L'espace pays des tests : la Suisse, seul pays actif (décision 2026-11-18-espace-pays).
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
        $this->seed(OfferDatabaseSeeder::class);
        Storage::fake(FormUpload::DISK);
        Storage::fake('public');
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
            'price' => '1850',
            'street' => 'Rue du Lac 4',
            'zip' => '1200',
            'city' => 'Genève',
            'status' => 'published',
            'property_type' => 'apartment',
            'rental_type' => 'furnished',
            'surface' => '64',
            'rooms' => '3.5',
            'bedrooms' => '2',
            'available_from' => '2026-12-01',
            'charges' => '180',
            // Au moins une photo (décision 2026-11-19-contrat-champ-upload-photos).
            'photos' => [$this->pendingUpload($user)],
            ...$overrides,
        ];
    }

    public function test_the_menu_links_to_my_homes(): void
    {
        $menu = $this->asUser($this->member())->getJson('/api/v1/auth/me/menu')->assertOk()->json('data');

        // Les groupes « Voyageur » (réservations, favoris) puis « Hôte » (logements), après « Compte » ;
        // plus de « Réservations reçues » (décision 2026-11-19-contrat-reservations-logements-profil).
        $traveler = ['key' => 'traveler', 'label' => 'Voyageur'];
        $this->assertSame([
            ['key' => 'bookings', 'label' => 'Mes réservations', 'list' => 'offer.bookings', 'group' => $traveler],
            ['key' => 'favorites', 'label' => 'Mes favoris', 'list' => 'offer.favorites', 'group' => $traveler],
            ['key' => 'offers', 'label' => 'Mes logements', 'list' => 'offer.mine', 'group' => ['key' => 'host', 'label' => 'Hôte']],
        ], array_slice($menu, -3));
        $this->assertSame('account', $menu[0]['group']['key']);
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
            // Sans catégorie créée, pas de champ « Catégorie » (décision 2026-11-20-type-de-location-garder-categories).
            ['title', 'description', 'price', 'street', 'zip', 'city', 'indication', 'status', 'property_type', 'rental_type', 'surface', 'rooms', 'bedrooms', 'available_from', 'amenities', 'charges', 'pets', 'smoking', 'parties', 'documents', 'informations', 'photos'],
            array_keys($fields),
        );
        $this->assertSame('Loyer', $fields['price']['label']);
        // L'unité suit le type de location pendant la saisie.
        $this->assertSame('CHF / mois', $fields['price']['suffix']);
        $this->assertSame(['field' => 'rental_type', 'suffixes' => ['furnished' => 'CHF / jour', 'long_term' => 'CHF / mois', 'shared' => 'CHF / mois']], $fields['price']['suffix_when']);
        // Le type de location, obligatoire, avec ses trois valeurs.
        $this->assertTrue($fields['rental_type']['required']);
        $this->assertSame(['Location longue durée', 'Location meublée', 'Colocation'], array_column($fields['rental_type']['options'], 'label'));
        $this->assertSame('Surface', $fields['surface']['label']);
        $this->assertSame('m²', $fields['surface']['suffix']);
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
        // Les charges : pour une location longue durée ou une colocation.
        $this->assertSame(['field' => 'rental_type', 'value' => 'long_term', 'values' => ['long_term', 'shared']], $fields['charges']['visible_when']);
        $this->assertFalse($fields['surface']['required']);
        $this->assertFalse($fields['rooms']['required']);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['rental_type' => 'furnished', 'charges' => '180']))->assertNoContent();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['rental_type' => 'long_term', 'charges' => '180', 'surface' => null, 'rooms' => null]))->assertNoContent();

        [$furnished, $unfurnished] = Offer::query()->orderBy('id')->get()->all();
        $this->assertNull($furnished->detail('charges'));
        $this->assertSame(180, $unfurnished->detail('charges'));
        $this->assertNull($unfurnished->detail('surface'));
    }

    public function test_the_app_form_comes_in_the_5_steps_of_the_domain(): void
    {
        $user = $this->member();
        UserAddress::factory()->for($user)->create();

        // Décision 2026-11-19-contrat-formulaire-etapes-symbole-devise.
        $form = $this->asUser($user)->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data');
        $steps = $form['steps'];
        // Titres centrés (décision 2026-11-19-alignement-titre-formulaire).
        $this->assertSame('center', $form['title_align']);
        // Décision 2026-11-19-etapes-formulaire-ordre-final.
        // L'étape 1 « Présentation » (décision 2026-11-19-etape-1-presentation), les photos en dernier
        // (décision 2026-11-19-contrat-champ-upload-photos).
        // « Règles » en 4e, puis « Informations » (décisions 2026-11-19-contrat-regles-informations et 2026-11-19-etape-informations).
        $this->assertSame(['Présentation', 'Caractéristiques', 'Prix et disponibilité', 'Règles', 'Informations', 'Localisation', 'Photos'], array_column($steps, 'title'));
        $this->assertSame([
            // Sans catégorie créée, pas de champ « Catégorie ».
            ['property_type', 'rental_type', 'title', 'description'],
            ['surface', 'rooms', 'bedrooms', 'amenities'],
            ['price', 'charges', 'available_from', 'status'],
            ['pets', 'smoking', 'parties', 'documents'],
            ['informations'],
            ['street', 'zip', 'city', 'indication'],
            ['photos'],
        ], array_column($steps, 'fields'));

        // « Suivant » : seule l'étape est vérifiée.
        $this->asUser($user)->postJson('/api/v1/forms/offer.offer/steps/1', ['property_type' => 'castle'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_type', 'title'])
            ->assertJsonMissingValidationErrors(['surface', 'rooms', 'price', 'street']);
        // Surface et pièces facultatives (décision 2026-11-19-charges-meuble-surface-facultative).
        $this->asUser($user)->postJson('/api/v1/forms/offer.offer/steps/1', ['property_type' => 'studio', 'rental_type' => 'long_term', 'title' => 'Studio'])->assertNoContent();
    }

    public function test_rules_and_information_are_optional_and_saved(): void
    {
        $user = $this->member();
        // Décision 2026-11-19-contrat-regles-informations.
        $fields = array_column($this->asUser($user)->getJson('/api/v1/forms/offer.offer')->json('data.fields'), null, 'name');
        // Plus d'heures d'arrivée ni de départ : un bloc d'informations les dit
        // (décision 2026-11-19-arrivee-depart-en-informations).
        $this->assertArrayNotHasKey('check_in_from', $fields);
        $this->assertArrayNotHasKey('check_out_until', $fields);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, [
            'check_in_from' => '15:00', 'min_age' => '21', 'documents' => ['id_card', 'deposit'], 'pets' => 'no',
        ]))->assertNoContent();
        $offer = Offer::query()->sole();
        $this->assertNull($offer->detail('check_in_from'));
        // Plus d'âge minimum propre à l'offre (décision 2026-11-19-age-minimum-de-l-app).
        $this->assertNull($offer->detail('min_age'));
        $this->assertSame('no', $offer->detail('pets'));

        // Valeurs inconnues ou hors limites refusées ; tout le reste est facultatif.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['documents' => ['visa'], 'pets' => 'maybe']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documents.0', 'pets']);
    }

    public function test_information_blocks_are_added_edited_and_removed_in_order(): void
    {
        $user = $this->member();
        // Décision 2026-11-19-contrat-offer-informations.
        $fields = array_column($this->asUser($user)->getJson('/api/v1/forms/offer.offer')->json('data.fields'), null, 'name');
        $this->assertSame('repeater', $fields['informations']['type']);
        $this->assertSame(['id', 'title', 'description'], array_column($fields['informations']['repeater']['fields'], 'name'));
        $this->assertSame(10, $fields['informations']['repeater']['max_items']);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['informations' => [
            ['title' => 'Arrivée', 'description' => "Clés chez le gardien.\nAppeler la veille."],
            ['title' => 'Parking', 'description' => 'Dans la cour.'],
        ]]))->assertNoContent();
        $offer = Offer::query()->sole();
        $this->assertSame(['Arrivée', 'Parking'], $offer->informations->map(fn ($information) => $information->getTranslation('title', 'fr'))->all());
        [$arrival, $parking] = $offer->informations->all();
        $arrival->setTranslation('title', 'en', 'Arrival')->save();

        // Le formulaire les reprend ; on modifie le premier, on retire le second, on en ajoute un.
        $defaults = array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data.fields'), 'default', 'name');
        $this->assertSame([['id' => (string) $arrival->getKey(), 'title' => 'Arrivée', 'description' => "Clés chez le gardien.\nAppeler la veille."], ['id' => (string) $parking->getKey(), 'title' => 'Parking', 'description' => 'Dans la cour.']], $defaults['informations']);
        $photos = $offer->getMedia(Offer::PHOTOS)->pluck('uuid')->all();
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", $this->input($user, ['photos' => $photos, 'informations' => [
            ['title' => 'Animaux', 'description' => 'Non acceptés.'],
            ['id' => (string) $arrival->getKey(), 'title' => 'Arrivée tardive', 'description' => 'Après 22 h, prévenir.'],
        ]]))->assertNoContent();

        $informations = $offer->fresh()->informations;
        $this->assertSame(['Animaux', 'Arrivée tardive'], $informations->map(fn ($information) => $information->getTranslation('title', 'fr'))->all());
        // Le bloc modifié garde son anglais ; le retiré n'existe plus.
        $this->assertSame('Arrival', $informations[1]->getTranslation('title', 'en'));
        $this->assertNull($parking->fresh());

        // Titre et description obligatoires, 10 blocs au plus.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['informations' => [['title' => '', 'description' => 'x']]]))
            ->assertUnprocessable()->assertJsonValidationErrors(['informations.0.title']);
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['informations' => array_fill(0, 11, ['title' => 'T', 'description' => 'D'])]))
            ->assertUnprocessable()->assertJsonValidationErrors(['informations']);
    }

    public function test_editing_asks_the_writing_language_prefills_and_saves_only_that_language(): void
    {
        // Décision 2026-11-19-contrat-langue-saisie-allemand.
        $user = $this->member();
        $form = $this->asUser($user)->getJson('/api/v1/forms/offer.offer')->json('data');
        // À la création : pas d'étape « Langue ».
        $this->assertNotContains('content_locale', array_column($form['fields'], 'name'));

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['title' => 'Appartement lumineux', 'indication' => 'Portail bleu', 'informations' => [['title' => 'Arrivée', 'description' => 'Clés chez le gardien.']]]))->assertNoContent();
        $offer = Offer::query()->sole();
        $this->assertSame('fr', $offer->locale);
        $this->assertSame(['fr' => 'Appartement lumineux'], $offer->getTranslations('title'));

        // À l'édition : l'étape « Langue » en premier, l'état de chaque langue, un champ qui recharge.
        $form = $this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data');
        $this->assertSame(['content_locale'], $form['steps'][0]['fields']);
        $this->assertSame('Langue', $form['steps'][0]['title']);
        $field = array_column($form['fields'], null, 'name')['content_locale'];
        $this->assertTrue($field['reloads']);
        $this->assertSame('fr', $field['default']);
        $this->assertSame(['Français — langue d\'origine', 'English — à traduire'], array_column($field['options'], 'label'));

        // « Suivant » sur l'étape « Langue » ne vérifie que la langue (étapes de l'offre modifiée).
        $this->asUser($user)->postJson("/api/v1/forms/offer.offer/steps/1?record={$offer->getKey()}", ['content_locale' => 'en'])->assertNoContent();
        $this->asUser($user)->postJson("/api/v1/forms/offer.offer/steps/1?record={$offer->getKey()}", ['content_locale' => 'xx'])
            ->assertUnprocessable()->assertJsonValidationErrors('content_locale');

        // L'anglais choisi : les textes sont pré-remplis avec l'original.
        $defaults = array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}&content_locale=en")->json('data.fields'), 'default', 'name');
        $this->assertSame('en', $defaults['content_locale']);
        $this->assertSame('Appartement lumineux', $defaults['title']);
        $this->assertSame('Arrivée', $defaults['informations'][0]['title']);
        $this->assertSame('Portail bleu', $defaults['indication']);

        // Enregistrer en anglais n'ajoute que l'anglais.
        $photos = $offer->getMedia(Offer::PHOTOS)->pluck('uuid')->all();
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", $this->input($user, [
            'content_locale' => 'en', 'photos' => $photos, 'title' => 'Bright flat', 'indication' => 'Blue gate',
            'informations' => [['id' => $defaults['informations'][0]['id'], 'title' => 'Arrival', 'description' => 'Keys with the caretaker.']],
        ]))->assertNoContent();
        $offer->refresh();
        $this->assertSame(['fr' => 'Appartement lumineux', 'en' => 'Bright flat'], $offer->getTranslations('title'));
        $this->assertSame('fr', $offer->locale);
        $this->assertSame(['fr' => 'Arrivée', 'en' => 'Arrival'], $offer->informations->sole()->getTranslations('title'));
        // L'indication de l'adresse aussi, par langue (décision 2026-11-19-indication-traduisible).
        $this->assertSame(['fr' => 'Portail bleu', 'en' => 'Blue gate'], $offer->offerAddress->getTranslations('indication'));
        // Lue par un visiteur anglophone (la langue d'un compte connecté primerait).
        app('auth')->forgetGuards();
        $this->flushHeaders();
        $this->assertSame('Blue gate', $this->withHeader('Accept-Language', 'en')->getJson("/api/v1/offers/{$offer->getKey()}")->json('data.indication'));
        $this->assertSame('Portail bleu', $this->withHeader('Accept-Language', 'fr')->getJson("/api/v1/offers/{$offer->getKey()}")->json('data.indication'));
        $labels = array_column(array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data.fields'), null, 'name')['content_locale']['options'], 'label');
        $this->assertSame('English — traduite', $labels[1]);

        // Une langue inconnue est refusée ; une app à une seule langue n'a pas d'étape.
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", $this->input($user, ['content_locale' => 'xx', 'photos' => $photos]))
            ->assertUnprocessable()->assertJsonValidationErrors('content_locale');
        config(['app.supported_locales' => ['fr']]);
        $this->assertNotContains('content_locale', array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data.fields'), 'name'));
    }

    public function test_a_missing_text_shows_in_the_original_language_before_the_default_one(): void
    {
        // Décision 2026-11-19-contrat-langue-saisie-allemand : demandée, d'origine, par défaut, une autre.
        config(['app.supported_locales' => ['fr', 'en', 'de']]);
        $offer = Offer::factory()->create(['locale' => 'en', 'title' => ['fr' => 'Studio', 'en' => 'Studio flat'], 'details' => ['note' => ['fr' => 'Calme', 'en' => 'Quiet']]]);
        OfferInformation::factory()->for($offer)->create(['title' => ['fr' => 'Arrivée', 'en' => 'Arrival']]);

        $this->assertSame('Studio flat', $offer->getTranslation('title', 'de'));
        $this->assertSame('Studio', $offer->getTranslation('title', 'fr'));
        $this->assertSame('Quiet', $offer->detailIn('note', 'de'));
        $this->assertSame('Arrival', $offer->informations->sole()->getTranslation('title', 'de'));
        // Sans langue d'origine connue : la langue par défaut, comme avant.
        $offer->locale = null;
        $this->assertSame('Studio', $offer->getTranslation('title', 'de'));
    }

    public function test_a_member_adds_a_home_with_its_attributes(): void
    {
        $user = $this->member();

        // Le type de location est obligatoire (décision 2026-11-20-contrat-type-de-location).
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['rental_type' => null]))->assertJsonValidationErrors('rental_type');
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['rental_type' => 'castle']))->assertJsonValidationErrors('rental_type');

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user))->assertNoContent();

        $offer = Offer::query()->sole();
        $this->assertTrue($offer->user->is($user));
        $this->assertSame(OfferStatusEnum::PUBLISHED, $offer->status);
        $this->assertNotNull($offer->published_at);
        $this->assertSame('1850.00', $offer->price);
        $this->assertSame('CHF', $offer->currency);
        $this->assertSame('apartment', $offer->detail('property_type'));
        $this->assertSame('furnished', $offer->detail('rental_type'));
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
        $this->assertTrue(Offer::query()->latest('id')->first()->address->is($existing));

        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['street' => 'Chemin Neuf 1', 'zip' => '', 'city' => 'Nyon']))->assertNoContent();
        $created = Offer::query()->latest('id')->first()->address;
        $this->assertSame(['Chemin Neuf 1', '', 'Nyon'], [$created->street, $created->zip, $created->city]);
        $this->assertSame(2, Address::query()->count());
        // Le carnet du compte n'est ni lu ni rempli.
        $this->assertSame(0, $user->addresses()->count());

        // Rue et ville obligatoires.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['street' => '', 'city' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['street', 'city']);
    }

    public function test_each_offer_has_its_own_indication_on_a_shared_address(): void
    {
        $user = $this->member();

        // Décision 2026-11-19-contrat-adresse-offre-table : l'adresse est partagée, l'indication non.
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['indication' => ' Portail bleu, 2e étage ']))->assertNoContent();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['indication' => '']))->assertNoContent();
        [$first, $second] = Offer::query()->with('offerAddress')->orderBy('id')->get()->all();

        $this->assertSame($first->offerAddress->address_id, $second->offerAddress->address_id);
        $this->assertSame(1, Address::query()->count());
        $this->assertSame('Portail bleu, 2e étage', $first->offerAddress->indication);
        $this->assertSame([], $second->offerAddress->getTranslations('indication'));
        $this->assertSame('Portail bleu, 2e étage', array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$first->getKey()}")->json('data.fields'), 'default', 'name')['indication']);
        $this->assertSame('Portail bleu, 2e étage', $this->getJson("/api/v1/offers/{$first->getKey()}")->json('data.indication'));
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['indication' => str_repeat('a', 501)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('indication');
    }

    public function test_editing_moves_the_offer_address_keeps_or_clears_the_indication(): void
    {
        $user = $this->member();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['indication' => 'Portail bleu']))->assertNoContent();
        $offer = Offer::query()->sole();
        $shared = $offer->address;
        $input = $this->input($user);
        unset($input['indication']);

        // Sans le champ envoyé : l'indication reste ; une autre adresse : la ligne la suit, l'ancienne adresse est intacte.
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", [...$input, 'street' => 'Chemin Neuf 1', 'city' => 'Nyon'])->assertNoContent();
        $offer->refresh();
        $this->assertSame('Chemin Neuf 1', $offer->address->street);
        $this->assertSame('Portail bleu', $offer->offerAddress->indication);
        $this->assertSame(1, OfferAddress::query()->count());
        $this->assertNotNull($shared->fresh());

        // Un champ vide retire l'indication (les photos déjà enregistrées sont renvoyées).
        $photos = $offer->getMedia(Offer::PHOTOS)->pluck('uuid')->all();
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", [...$input, 'photos' => $photos, 'indication' => ''])->assertNoContent();
        $this->assertSame([], $offer->offerAddress()->sole()->getTranslations('indication'));

        // Supprimer l'adresse retire la ligne, pas l'offre.
        $offer->fresh()->address->delete();
        $this->assertSame(0, OfferAddress::query()->count());
        $this->assertNotNull($offer->fresh());
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
            // « Ajouter un logement » dans la barre du bas (décision 2026-11-19-contrat-reservations-logements-profil).
            ->assertJsonPath('data.create', null)
            ->assertJsonPath('data.footer', ['label' => 'Ajouter un logement', 'form' => 'offer.offer', 'link' => null])
            ->assertJsonPath('data.search', 'Rechercher par titre…')
            ->json('data.items');

        $this->assertCount(1, $data);
        $this->assertSame((string) $mine->getKey(), $data[0]['id']);
        $this->assertSame('Studio', $data[0]['title']);
        // Statut, détails et prix en gras ; le titre saisi en sous-titre s'il diffère.
        // Le statut en pastille verte ; plus dans la ligne en gras (décision 2026-11-19-statut-carte-logement).
        $this->assertSame(['label' => 'Publiée', 'tone' => 'success'], $data[0]['tag']);
        $this->assertStringNotContainsString('Publiée', $data[0]['highlight']);
        $this->assertStringContainsString('900', $data[0]['highlight']);
        $this->assertSame("/v1/offers/{$mine->getKey()}", $data[0]['delete']['endpoint']);
        // « Voir » (sa fiche) et « Éditer » (le formulaire) (décision 2026-11-19-nuit-voir-editer-retirer).
        $this->assertSame("/offers/{$mine->getKey()}", $data[0]['link']);
        $this->assertSame('offer.offer', $data[0]['edit']['form']);
    }

    public function test_the_list_shows_the_expiration_date_after_the_status(): void
    {
        $user = $this->member();
        // Décision 2026-11-19-expiration-mes-logements.
        Offer::factory()->for($user)->create(['title' => 'A', 'status' => 'published', 'expires_at' => '2027-06-30 12:00:00']);
        Offer::factory()->for($user)->create(['title' => 'B', 'status' => 'published', 'expires_at' => now()->subDay()->setDate(2026, 1, 2)]);
        Offer::factory()->for($user)->create(['title' => 'C', 'status' => 'draft']);

        $highlights = array_column($this->asUser($user)->getJson('/api/v1/lists/offer.mine')->assertOk()->json('data.items'), 'highlight', 'title');

        $this->assertStringStartsWith("Payé jusqu'au 30 juin 2027", $highlights['A']);
        $this->assertStringStartsWith('Paiement échu le 2 janvier 2026', $highlights['B']);
        $this->assertStringNotContainsString('Expir', (string) $highlights['C']);
    }

    public function test_editing_prefills_and_updates_my_offer(): void
    {
        $user = $this->member();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input($user, ['amenities' => ['wifi', 'parking']]))->assertNoContent();
        $offer = Offer::query()->sole();

        $fields = array_column($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->assertOk()
            ->assertJsonPath('data.submit.method', 'PATCH')
            ->json('data.fields'), 'default', 'name');
        $this->assertSame('Appartement lumineux', $fields['title']);
        $this->assertSame('apartment', $fields['property_type']);
        // Les équipements choisis, tous cochés (une liste n'est pas une traduction).
        $this->assertSame(['wifi', 'parking'], $fields['amenities']);
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
