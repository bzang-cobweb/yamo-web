<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Filament\Resources\OfferResource\RelationManagers\ReviewsRelationManager;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Les avis d'une offre, en onglet de sa fiche du back-office, et la section
 * « Accès » (décision 2026-11-19-acces-avis-backoffice).
 */
class OfferReviewsAndAccessTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
    }

    private function offer(): Offer
    {
        return Offer::factory()->at(Address::factory()->create())->create([
            'status' => 'published', 'title' => ['fr' => 'Studio'], 'price' => '1000', 'currency' => 'CHF',
            'details' => ['property_type' => 'studio', 'rental_type' => 'long_term'],
        ]);
    }

    public function test_the_reviews_tab_needs_the_permission_and_creates_nothing(): void
    {
        $offer = $this->offer();

        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $this->assertFalse(ReviewsRelationManager::canViewForRecord($offer, EditOffer::class));

        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS, PermissionEnum::VIEW_OFFER_REVIEWS);
        $this->assertTrue(ReviewsRelationManager::canViewForRecord($offer, EditOffer::class));
        // Aucun bouton de création : un avis vient de l'app seulement.
        $this->assertNull(Livewire::test(ReviewsRelationManager::class, ['ownerRecord' => $offer, 'pageClass' => EditOffer::class])->instance()->getTable()->getAction('create'));
    }

    public function test_the_admin_role_filters_and_deletes_the_reviews_of_an_offer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $this->actingAs($admin);
        $offer = $this->offer();
        $good = OfferReview::factory()->for($offer)->create(['rating' => 5, 'comment' => 'Parfait']);
        $bad = OfferReview::factory()->for($offer)->create(['rating' => 1, 'comment' => 'Insultant']);
        $elsewhere = OfferReview::factory()->create();

        $this->get(OfferResource::getUrl('edit', ['record' => $offer]))->assertOk();
        $this->assertSame('Avis (2)', ReviewsRelationManager::getTitle($offer, EditOffer::class));

        Livewire::test(ReviewsRelationManager::class, ['ownerRecord' => $offer, 'pageClass' => EditOffer::class])
            ->assertCanSeeTableRecords([$good, $bad])
            ->assertCanNotSeeTableRecords([$elsewhere])
            ->filterTable('rating', 1)
            ->assertCanSeeTableRecords([$bad])
            ->assertCanNotSeeTableRecords([$good])
            ->callAction(TestAction::make('delete')->table($bad));

        $this->assertModelMissing($bad);
        $this->assertModelExists($good);
    }

    public function test_the_access_section_saves_status_featured_available_and_paid_dates(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = $this->offer();

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->fillForm([
                'status' => 'draft',
                'featured_until' => '2027-01-31 12:00',
                'details.available_from' => '2026-12-15',
                'expires_at' => '2027-06-30 23:59',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $offer->refresh();
        $this->assertSame('draft', $offer->status->value);
        $this->assertSame('2027-06-30 23:59', $offer->expires_at?->format('Y-m-d H:i'));
        $this->assertSame('2026-12-15', $offer->detail('available_from'));
    }

    public function test_type_and_rental_type_come_before_the_category_and_the_rent_before_the_charges(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $html = $this->get(OfferResource::getUrl('edit', ['record' => $this->offer()]))->assertOk()->getContent();

        // Décision 2026-11-19-backoffice-loyer-type-annonce.
        // Les champs du formulaire, par leur chemin d'état (`data.…`), dans l'ordre de la page.
        $position = fn (string $path): int => (int) strpos((string) $html, '"data.'.$path.'"');
        foreach (['details.property_type', 'details.rental_type', 'category_id', 'details.bedrooms', 'price', 'details.charges'] as $path) {
            $this->assertGreaterThan(0, $position($path), $path);
        }
        $this->assertLessThan($position('category_id'), $position('details.property_type'));
        $this->assertLessThan($position('category_id'), $position('details.rental_type'));
        // Le loyer puis les charges dans l'onglet « Prix », après « Localisation » et avant les
        // caractéristiques (décision 2026-11-19-onglet-prix).
        $this->assertLessThan($position('price'), $position('category_id'));
        $this->assertLessThan($position('details.charges'), $position('price'));
        $this->assertLessThan($position('details.bedrooms'), $position('details.charges'));
    }

    public function test_the_country_is_marked_required_even_when_it_is_the_only_one(): void
    {
        // Décision 2026-11-19-pays-obligatoire-visible : un seul pays actif, la liste est
        // pré-remplie sans être désactivée, et son astérisque reste visible.
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = $this->offer();
        Country::query()->whereKeyNot($offer->country_id)->update(['active' => false]);
        $html = (string) $this->get(OfferResource::getUrl('edit', ['record' => $offer]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Pays\s*<sup class="fi-fo-field-label-required-mark">\*<\/sup>/', $html);
    }
}
