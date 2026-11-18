<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Address;
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
                'address_id' => $address->getKey(),
                'category_id' => (string) OfferCategory::query()->where('slug', 'long-term')->value('id'),
                'details.property_type' => 'house',
                'details.surface' => '120',
                'details.rooms' => '5.5',
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
                'address_id' => Address::factory()->create()->getKey(),
                'details.property_type' => 'castle',
                'details.surface' => '-5',
                'details.rooms' => '2',
            ])
            ->call('create')
            ->assertHasFormErrors(['details.property_type', 'details.surface']);
    }

    public function test_only_the_default_language_title_is_required(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::CREATE_OFFERS);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'user_id' => User::factory()->create()->getKey(),
                'status' => 'draft',
                'title.en' => 'Only English',
                'price' => '1000',
                'address_id' => Address::factory()->create()->getKey(),
                'details.property_type' => 'studio',
                'details.surface' => '20',
                'details.rooms' => '1',
            ])
            ->call('create')
            ->assertHasFormErrors(['title.fr'])
            ->assertHasNoFormErrors(['title.en']);
    }

    public function test_editing_keeps_the_attributes(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = Offer::factory()->create(['price' => '1000', 'currency' => 'CHF', 'address_id' => Address::factory()->create()->getKey(), 'details' => ['property_type' => 'studio', 'surface' => 30, 'rooms' => 1]]);

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSchemaStateSet(['details.property_type' => 'studio', 'title.fr' => $offer->getTranslation('title', 'fr')])
            ->fillForm(['details.surface' => '32'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(32, $offer->fresh()->detail('surface'));
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
