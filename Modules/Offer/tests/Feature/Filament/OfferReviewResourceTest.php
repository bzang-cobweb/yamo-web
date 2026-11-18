<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Country;
use Modules\Offer\Filament\Resources\OfferReviewResource;
use Modules\Offer\Filament\Resources\OfferReviewResource\Pages\ListOfferReviews;
use Modules\Offer\Models\OfferReview;

/**
 * Modération des avis dans le back-office (décision 2026-11-19-contrat-avis-offres).
 */
class OfferReviewResourceTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
    }

    public function test_without_the_permission_there_is_no_access(): void
    {
        $this->actingAsUserWith();
        $this->get(OfferReviewResource::getUrl('index'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::VIEW_OFFER_REVIEWS);
        $this->get(OfferReviewResource::getUrl('index'))->assertOk();
        $this->assertFalse(OfferReviewResource::canCreate());

        // Pas même le super administrateur ne crée ni ne modifie un avis.
        $this->actingAsSuperAdmin();
        $this->assertFalse(OfferReviewResource::canCreate());
        $this->assertFalse(OfferReviewResource::canEdit(OfferReview::factory()->create()));
    }

    public function test_the_admin_role_lists_filters_and_deletes_reviews(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $this->actingAs($admin);
        $good = OfferReview::factory()->create(['rating' => 5, 'comment' => 'Parfait']);
        $bad = OfferReview::factory()->create(['rating' => 1, 'comment' => 'Insultant']);

        $this->get(OfferReviewResource::getUrl('index'))->assertOk()->assertSee('Avis');

        Livewire::test(ListOfferReviews::class)
            ->assertCanSeeTableRecords([$good, $bad])
            ->filterTable('rating', 1)
            ->assertCanSeeTableRecords([$bad])
            ->assertCanNotSeeTableRecords([$good])
            ->resetTableFilters()
            ->filterTable('offer_id', $good->offer_id)
            ->assertCanSeeTableRecords([$good])
            ->assertCanNotSeeTableRecords([$bad])
            ->resetTableFilters()
            ->callAction(TestAction::make('delete')->table($bad));

        $this->assertModelMissing($bad);
        $this->assertModelExists($good);
    }
}
