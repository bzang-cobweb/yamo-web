<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Tests\TestCase;

/**
 * La confidentialité d'un membre là où un autre le voit (décision 2026-11-27-confidentialite) : sa page publique selon
 * sa « Visibilité du profil » (404 sinon), et son lieu sous ses avis s'il l'autorise.
 */
class MemberPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        $this->cameroon = Country::factory()->create(['code' => 'CM', 'currency' => 'XAF', 'name' => ['fr' => 'Cameroun', 'en' => 'Cameroon']]);
        $this->seed(OfferDatabaseSeeder::class);
        $this->app->instance(SmsSender::class, new FakeSmsSender);
    }

    private function page(User $viewer, User $member): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($viewer->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson("/api/v1/members/{$member->getKey()}");
    }

    private function member(string $visibility): User
    {
        return User::factory()->create(['privacy_settings' => ['profile_visibility' => $visibility]]);
    }

    public function test_anyone_sees_a_profile_open_to_everyone(): void
    {
        $this->page(User::factory()->create(), User::factory()->create())->assertOk();
        $this->page(User::factory()->create(), $this->member('anyone'))->assertOk();
    }

    public function test_owners_only_needs_a_viewer_with_an_offer(): void
    {
        $member = $this->member('owners');
        $viewer = User::factory()->create();

        $this->page($viewer, $member)->assertNotFound();
        Offer::factory()->for($viewer, 'user')->create();
        $this->page($viewer, $member)->assertOk();
        // Sa propre page, toujours.
        $this->page($member, $member)->assertOk();
    }

    public function test_connections_only_needs_a_shared_request_or_message(): void
    {
        $member = $this->member('connections');
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        Offer::factory()->for($owner, 'user')->create();

        $this->page($viewer, $member)->assertNotFound();
        // Un propriétaire sans lien : non plus.
        $this->page($owner, $member)->assertNotFound();

        // Le visiteur a demandé une offre du membre.
        $offer = Offer::factory()->for($member, 'user')->create();
        Booking::factory()->for($viewer)->for($offer)->create();
        $this->page($viewer, $member)->assertOk();

        // Un message du membre sur une offre du propriétaire.
        $ownerOffer = Offer::factory()->for($owner, 'user')->create(['status' => 'published', 'title' => ['fr' => 'Studio']]);
        $this->app['auth']->forgetGuards();
        $this->withToken($member->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->postJson("/api/v1/offers/{$ownerOffer->getKey()}/messages", ['message' => 'Bonjour, est-ce libre ?'])->assertSuccessful();
        $this->page($owner, $member)->assertOk();
    }

    public function test_owners_and_connections_accepts_either(): void
    {
        $member = $this->member('owners_and_connections');
        $stranger = User::factory()->create();
        $owner = User::factory()->create();
        Offer::factory()->for($owner, 'user')->create();

        $this->page($stranger, $member)->assertNotFound();
        $this->page($owner, $member)->assertOk();
    }

    public function test_a_review_shows_the_author_location_only_when_allowed(): void
    {
        $region = Region::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Littoral', 'en' => 'Littoral']]);
        $author = User::factory()->create(['first_name' => 'Awa', 'country_id' => $this->cameroon->getKey(), 'region_id' => $region->getKey()]);
        $offer = Offer::factory()->create(['status' => 'published', 'title' => ['fr' => 'Studio']]);
        OfferReview::query()->create(['offer_id' => $offer->getKey(), 'user_id' => $author->getKey(), 'rating' => 5, 'comment' => 'Parfait.']);
        $reviews = fn (): array => $this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson("/api/v1/offers/{$offer->getKey()}/reviews")->assertOk()->json('data.items');

        $this->assertNull($reviews()[0]['author_location']);

        $author->forceFill(['privacy_settings' => ['review_location' => true]])->save();
        $this->assertSame('Littoral, Cameroun', $reviews()[0]['author_location']);
    }
}
