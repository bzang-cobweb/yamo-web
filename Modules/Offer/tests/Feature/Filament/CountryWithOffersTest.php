<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Filament\Actions\DeleteAction;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;

/**
 * Le pays d'une offre est obligatoire jusque dans la base (décision
 * 2026-11-19-pays-obligatoire-offre) : pas d'offre sans pays, et un pays qui a
 * des offres ne se supprime pas ; le back-office le dit au lieu d'échouer.
 */
class CountryWithOffersTest extends AuthFilamentTestCase
{
    public function test_an_offer_cannot_be_saved_without_a_country(): void
    {
        $this->expectException(QueryException::class);

        Offer::factory()->create(['country_id' => null]);
    }

    public function test_a_country_with_offers_is_kept_with_a_message(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::DELETE_COUNTRIES);
        $offer = Offer::factory()->create();
        $country = $offer->country;

        Livewire::test(EditCountry::class, ['record' => $country->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified('Ce pays a encore des offres : il ne peut pas être supprimé.');

        $this->assertModelExists($country);
        $this->assertTrue($offer->fresh()->country->is($country));
    }

    public function test_a_country_without_offers_is_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_COUNTRIES, PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::DELETE_COUNTRIES);
        $country = Country::factory()->create();

        Livewire::test(EditCountry::class, ['record' => $country->getKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($country);
    }
}
