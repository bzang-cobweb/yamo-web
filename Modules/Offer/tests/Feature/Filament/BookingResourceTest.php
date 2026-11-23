<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Filament\Resources\BookingResource;
use Modules\Offer\Filament\Resources\BookingResource\Pages\ListBookings;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Filament\Resources\OfferResource\RelationManagers\BookingsRelationManager;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Les réservations au back-office, en lecture seule (décision
 * 2026-11-19-contrat-reservations) : pour qui voit les offres ; ni création, ni
 * modification, ni suppression.
 */
class BookingResourceTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
    }

    public function test_bookings_are_listed_and_viewed_read_only_by_who_sees_offers(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS);
        $pending = Booking::factory()->create(['message' => 'Bonjour !']);
        $refused = Booking::factory()->status(BookingStatusEnum::REFUSED)->create();

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$pending, $refused])
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->filterTable('status', BookingStatusEnum::REFUSED->value)
            ->assertCanSeeTableRecords([$refused])
            ->assertCanNotSeeTableRecords([$pending]);

        $this->get(BookingResource::getUrl('view', ['record' => $pending]))->assertOk()->assertSee('Bonjour !')->assertSee('En attente');
        $this->assertFalse(BookingResource::canCreate());
        $this->assertFalse(BookingResource::canEdit($pending));
        $this->assertFalse(BookingResource::canDelete($pending));
    }

    public function test_an_offer_lists_its_own_bookings_read_only(): void
    {
        // Décision 2026-11-23-demandes-dans-l-offre.
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = Offer::factory()->create();
        $mine = Booking::factory()->for($offer)->create();
        $refused = Booking::factory()->for($offer)->status(BookingStatusEnum::REFUSED)->create();
        $other = Booking::factory()->create();

        $this->assertContains(BookingsRelationManager::class, OfferResource::getRelations());
        $this->assertSame('Réservations (2)', BookingsRelationManager::getTitle($offer, EditOffer::class));

        Livewire::test(BookingsRelationManager::class, ['ownerRecord' => $offer, 'pageClass' => EditOffer::class])
            ->assertCanSeeTableRecords([$mine, $refused])
            ->assertCanNotSeeTableRecords([$other])
            ->assertCountTableRecords(2)
            ->assertTableColumnHidden('offer.title')
            ->assertTableColumnExists('user.name')
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->filterTable('status', BookingStatusEnum::REFUSED->value)
            ->assertCanSeeTableRecords([$refused])
            ->assertCanNotSeeTableRecords([$mine]);
    }

    public function test_hidden_without_the_permission_to_see_offers(): void
    {
        $this->actingAsUserWith();
        $booking = Booking::factory()->create();

        $this->get(BookingResource::getUrl('index'))->assertForbidden();
        $this->get(BookingResource::getUrl('view', ['record' => $booking]))->assertForbidden();
    }
}
