<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Filament\Forms\Components\Toggle;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Filament\Resources\OfferCategoryResource;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\CreateOfferCategory;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Filament\Resources\OfferResource\Pages\CreateOffer;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Filament\Resources\OfferResource\Pages\ListOffers;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

/**
 * Offres et catégories dans le back-office, domaine « location » (décision
 * 2026-11-18-offres-et-domaines).
 */
class OfferResourceTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        // L'espace pays des tests : la Suisse, seul pays actif (décision 2026-11-18-espace-pays).
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    public function test_without_offers_permission_or_without_domain_there_is_no_access(): void
    {
        $this->actingAsUserWith();
        $this->get(OfferResource::getUrl('index'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS);
        config(['offer.domain' => null]);
        $this->assertFalse(OfferResource::canAccess());
        $this->assertFalse(OfferCategoryResource::canAccess());
    }

    public function test_the_admin_role_lists_and_filters_all_offers_including_removed_ones(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $this->actingAs($admin);
        $published = Offer::factory()->create(['status' => 'published']);
        $draft = Offer::factory()->create(['status' => 'draft']);
        $removed = Offer::factory()->create();
        $removed->delete();

        $this->get(OfferResource::getUrl('index'))->assertOk()->assertSee('Logements');

        Livewire::test(ListOffers::class)
            ->assertCanSeeTableRecords([$published, $draft])
            ->filterTable('status', 'published')
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft])
            ->resetTableFilters()
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$removed]);
    }

    public function test_creating_an_offer_with_its_domain_attributes(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::CREATE_OFFERS);
        $owner = User::factory()->create();
        $address = Address::factory()->create();

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'user_id' => $owner->getKey(),
                'status' => 'published',
                'title.fr' => 'Maison avec jardin',
                'title.en' => 'House with a garden',
                'price' => '2400',
                'offerAddress.address_id' => $address->getKey(),
                'offerAddress.indication.fr' => 'Portail vert',
                'category_id' => (string) OfferCategory::query()->where('slug', 'long-term')->value('id'),
                'details.property_type' => 'house',
                'details.rental_type' => 'long_term',
                'details.surface' => '120',
                'details.rooms' => '5.5',
                'details.bedrooms' => '3',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = Offer::query()->sole();
        $this->assertTrue($offer->user->is($owner));
        $this->assertSame('house', $offer->detail('property_type'));
        $this->assertSame(['fr' => 'Maison avec jardin', 'en' => 'House with a garden'], $offer->getTranslations('title'));
        $this->assertSame(5.5, $offer->detail('rooms'));
        $this->assertSame('CHF', $offer->currency);
        $this->assertNotNull($offer->published_at);
        $this->assertTrue($offer->address->is($address));
        $this->assertSame(['fr' => 'Portail vert'], $offer->offerAddress->getTranslations('indication'));
    }

    public function test_an_admin_features_an_offer_until_a_date_and_filters_featured_ones(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $this->actingAs($admin);
        $offer = Offer::factory()->at(Address::factory()->create())->create([
            'status' => 'published', 'title' => ['fr' => 'Studio'], 'price' => '1000', 'currency' => 'CHF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'long_term', 'bedrooms' => 0],
        ]);
        $other = Offer::factory()->create(['status' => 'published']);

        // Décision 2026-11-19-contrat-vedette-favoris.
        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->fillForm(['featured_until' => now()->addDays(10)->format('Y-m-d H:i')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($offer->fresh()->isFeatured());
        Livewire::test(ListOffers::class)
            ->filterTable('featured', true)
            ->assertCanSeeTableRecords([$offer])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_an_admin_chooses_to_show_the_address_or_not(): void
    {
        // Décision 2026-11-21-afficher-adresse : le même interrupteur que dans l'app.
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $this->actingAs($admin);
        $offer = Offer::factory()->at(Address::factory()->create())->create([
            'status' => 'published', 'title' => ['fr' => 'Studio'], 'price' => '1000', 'currency' => 'CHF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'long_term', 'bedrooms' => 0],
        ]);

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertFormSet(['show_address' => true])
            ->fillForm(['show_address' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($offer->fresh()->show_address);

        // Le contact de l'offre (décision 2026-11-21-contact-offre) : saisi en international, gardé en E.164.
        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->fillForm(['phone' => '+41 79 123 45 67', 'email' => 'contact@example.test'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['+41791234567', 'contact@example.test'], [$offer->fresh()->phone, $offer->fresh()->email]);
    }

    public function test_the_domain_schema_validates_in_the_back_office_too(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::CREATE_OFFERS);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'user_id' => User::factory()->create()->getKey(),
                'status' => 'draft',
                'title.fr' => 'X',
                'price' => '1000',
                'offerAddress.address_id' => Address::factory()->create()->getKey(),
                'details.property_type' => 'castle',
                'details.rental_type' => 'long_term',
                'details.surface' => '-5',
                'details.rooms' => '2',
            ])
            ->call('create')
            ->assertHasFormErrors(['details.property_type', 'details.surface']);
    }

    public function test_a_title_in_at_least_one_language_is_required(): void
    {
        // Décision 2026-11-19-contrat-langue-saisie-allemand.
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::CREATE_OFFERS);
        $form = fn (array $title): array => [
            'user_id' => User::factory()->create()->getKey(),
            'status' => 'draft',
            ...$title,
            'price' => '1000',
            'offerAddress.address_id' => Address::factory()->create()->getKey(),
            'details.property_type' => 'studio',
            'details.rental_type' => 'long_term',
            'details.surface' => '20',
            'details.rooms' => '1',
            'details.bedrooms' => '0',
        ];

        Livewire::test(CreateOffer::class)->fillForm($form([]))->call('create')->assertHasFormErrors(['title.fr']);

        Livewire::test(CreateOffer::class)->fillForm($form(['title.en' => 'Only English']))->call('create')->assertHasNoFormErrors();
        $offer = Offer::query()->sole();
        $this->assertSame(['en' => 'Only English'], $offer->getTranslations('title'));
        // Sa langue d'origine : l'anglais.
        $this->assertSame('en', $offer->locale);
    }

    public function test_offers_are_filtered_by_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS);
        $switzerland = Offer::factory()->create();
        $senegal = Offer::factory()->create(['country_id' => Country::factory()->create(['code' => 'SN'])->getKey()]);

        Livewire::test(ListOffers::class)
            ->assertCanSeeTableRecords([$switzerland, $senegal])
            ->filterTable('country_id', $senegal->country_id)
            ->assertCanSeeTableRecords([$senegal])
            ->assertCanNotSeeTableRecords([$switzerland]);
    }

    public function test_the_address_must_be_in_the_offer_country(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::CREATE_OFFERS);
        $elsewhere = Address::factory()->for(Country::factory()->create(['code' => 'SN']))->create();

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'user_id' => User::factory()->create()->getKey(),
                'country_id' => Country::query()->where('code', 'CH')->value('id'),
                'status' => 'draft',
                'title.fr' => 'Studio',
                'price' => '1000',
                'offerAddress.address_id' => $elsewhere->getKey(),
                'details.property_type' => 'studio',
                'details.rental_type' => 'long_term',
                'details.surface' => '20',
                'details.rooms' => '1',
            ])
            ->call('create')
            ->assertHasFormErrors(['offerAddress.address_id']);
    }

    public function test_editing_keeps_the_attributes(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = Offer::factory()->at(Address::factory()->create(), 'Portail bleu')->create(['price' => '1000', 'currency' => 'CHF', 'details' => ['property_type' => 'studio', 'rental_type' => 'long_term', 'surface' => 30, 'rooms' => 1, 'bedrooms' => 0]]);

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSchemaStateSet(['details.property_type' => 'studio', 'title.fr' => $offer->getTranslation('title', 'fr'), 'offerAddress.indication.fr' => 'Portail bleu'])
            ->fillForm(['details.surface' => '32'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(32, $offer->fresh()->detail('surface'));

        // Les onglets, dans leur ordre (décision 2026-11-19-onglet-prix).
        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSeeInOrder(['Général', 'Localisation', 'Prix', 'Accès', 'Caractéristiques', 'Règles', 'Informations', 'Photos']);

        // L'onglet « Règles » (décision 2026-11-19-contrat-regles-informations).
        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSee('Règles')
            ->assertFormFieldExists('details.pets', fn ($field): bool => $field instanceof Toggle)
            ->fillForm(['details.pets' => true, 'details.smoking' => false])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('yes', $offer->fresh()->detail('pets'));
        $this->assertSame('no', $offer->fresh()->detail('smoking'));

        // Les blocs d'informations, par langue (décision 2026-11-19-contrat-offer-informations).
        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->fillForm(['informations' => [
                ['title' => ['fr' => 'Arrivée', 'en' => 'Arrival'], 'description' => ['fr' => 'Clés chez le gardien.', 'en' => 'Keys with the caretaker.']],
                ['title' => ['fr' => 'Parking'], 'description' => ['fr' => 'Dans la cour.']],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();
        $informations = $offer->fresh()->informations;
        $this->assertSame(['Arrivée', 'Parking'], $informations->map(fn ($information) => $information->getTranslation('title', 'fr'))->all());
        $this->assertSame('Arrival', $informations[0]->getTranslation('title', 'en'));
        // Le back-office numérote à partir de 1 ; seul l'ordre compte.
        $this->assertSame([1, 2], $informations->pluck('position')->all());
        $this->assertSame('yes', $offer->fresh()->detail('pets'));
        $this->assertSame('studio', $offer->fresh()->detail('property_type'));
    }

    public function test_categories_are_managed_with_the_offers_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS);
        $this->get(OfferCategoryResource::getUrl('create'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        Livewire::test(CreateOfferCategory::class)
            ->fillForm(['name.fr' => 'Saisonnier', 'name.en' => 'Seasonal', 'slug' => 'seasonal', 'position' => 4])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Saisonnier', OfferCategory::query()->where('slug', 'seasonal')->sole()->getTranslation('name', 'fr'));
    }
}
