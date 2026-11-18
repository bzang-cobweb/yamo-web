<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Tests\TestCase;

/**
 * Avis sur les offres (décision 2026-11-19-contrat-avis-offres) : un membre note
 * une offre publiée d'un autre membre ; les listes montrent la moyenne et le
 * dernier commentaire, la fiche les 5 derniers avis.
 */
class OfferReviewApiTest extends TestCase
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

    private function member(string $firstName = 'Awa'): User
    {
        $user = User::factory()->create(['first_name' => $firstName, 'locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function api(?User $user, string $method, string $uri, array $data = []): TestResponse
    {
        app('auth')->forgetGuards();
        // Pas de jeton gardé d'un appel précédent.
        $this->flushHeaders();
        $request = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);

        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri, $data);
    }

    private function review(User $user, Offer $offer, int $rating, string $comment): TestResponse
    {
        return $this->api($user, 'PUT', "/api/v1/offers/{$offer->getKey()}/review", ['rating' => $rating, 'comment' => $comment]);
    }

    public function test_a_member_reviews_another_members_offer_and_sees_it_in_the_list_and_the_detail(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $awa = $this->member('Awa');
        $paul = $this->member('Paul');

        $this->review($awa, $offer, 4, 'Très bon accueil.')->assertNoContent();
        $this->travel(1)->minutes();
        $this->review($paul, $offer, 5, "  Propre et   calme,\nje recommande.  ")->assertNoContent();

        $item = $this->api(null, 'GET', '/api/v1/lists/offer.browse')->assertOk()->json('data.items.0');
        $this->assertSame(['average' => 4.5, 'count' => 2], $item['rating']);
        // Le commentaire reste sur la fiche, pas dans la liste (décision 2026-11-19-item-titre-saisi-details).
        $this->assertStringNotContainsString('recommande', (string) $item['highlight']);

        $reviews = $this->api($awa, 'GET', "/api/v1/offers/{$offer->getKey()}")->assertOk()->json('data.reviews');
        $this->assertSame(4.5, $reviews['average']);
        $this->assertSame(2, $reviews['count']);
        $this->assertSame(['Paul', 'Awa'], array_column($reviews['latest'], 'author'));
        // Le prénom seulement : ni nom de famille, ni e-mail.
        $this->assertSame(['id', 'author', 'rating', 'comment', 'created_at'], array_keys($reviews['latest'][0]));
        $this->assertStringNotContainsString((string) $paul->email, json_encode($reviews, JSON_THROW_ON_ERROR));
        $this->assertSame(['rating' => 4, 'comment' => 'Très bon accueil.'], $reviews['mine']);
        $this->assertTrue($reviews['can_review']);
        $this->assertSame(['method' => 'DELETE', 'endpoint' => "/v1/offers/{$offer->getKey()}/review"], array_intersect_key($reviews['delete'], ['method' => 1, 'endpoint' => 1]));
    }

    public function test_one_review_per_member_and_offer_which_can_be_changed_or_removed(): void
    {
        $offer = Offer::factory()->create(['status' => 'published']);
        $awa = $this->member();

        $this->review($awa, $offer, 2, 'Bruyant.')->assertNoContent();
        $this->review($awa, $offer, 3, 'Bruyant, mais bien situé.')->assertNoContent();

        $this->assertSame(1, OfferReview::query()->count());
        $this->assertSame(3, OfferReview::query()->value('rating'));

        // Un avis modifié redevient le premier de la fiche.
        $this->travel(1)->minutes();
        $this->review($this->member('Paul'), $offer, 5, 'Parfait.')->assertNoContent();
        $this->travel(1)->minutes();
        $this->review($awa, $offer, 3, 'Bruyant le soir, mais bien situé.')->assertNoContent();
        $this->assertSame(['Awa', 'Paul'], array_column($this->api(null, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.reviews.latest'), 'author'));
        OfferReview::query()->whereNot('user_id', $awa->getKey())->delete();

        // Le formulaire, prérempli avec son avis.
        $form = $this->api($awa, 'GET', "/api/v1/forms/offer.review?record={$offer->getKey()}")->assertOk()->json('data');
        $this->assertSame('Modifier mon avis', $form['title']);
        $this->assertSame(['rating', 'textarea'], array_column($form['fields'], 'type'));
        $this->assertSame(['3', 'Bruyant le soir, mais bien situé.'], array_column($form['fields'], 'default'));
        $this->assertSame('PUT', $form['submit']['method']);

        $this->api($awa, 'DELETE', "/api/v1/offers/{$offer->getKey()}/review")->assertNoContent();
        $this->assertSame(0, OfferReview::query()->count());
        $this->assertNull($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.items.0.rating'));
    }

    public function test_the_api_refuses_what_the_app_hides(): void
    {
        $owner = $this->member();
        $offer = Offer::factory()->for($owner)->create(['status' => 'published']);
        $visitor = $this->member('Paul');

        // Le propriétaire : 403, même par le formulaire.
        $this->review($owner, $offer, 5, 'Parfait !')->assertForbidden();
        $this->api($owner, 'GET', "/api/v1/forms/offer.review?record={$offer->getKey()}")->assertForbidden();
        $this->assertFalse($this->api($owner, 'GET', "/api/v1/offers/{$offer->getKey()}")->json('data.reviews.can_review'));
        // Sans connexion.
        $this->api(null, 'PUT', "/api/v1/offers/{$offer->getKey()}/review", ['rating' => 5, 'comment' => 'Super'])->assertUnauthorized();
        // Note hors de 1–5, commentaire vide ou trop long.
        $this->review($visitor, $offer, 0, 'Bof')->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->review($visitor, $offer, 6, 'Bof')->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->review($visitor, $offer, 3, '')->assertUnprocessable()->assertJsonValidationErrors('comment');
        $this->review($visitor, $offer, 3, str_repeat('a', 1001))->assertUnprocessable()->assertJsonValidationErrors('comment');

        $this->assertSame(0, OfferReview::query()->count());
    }

    public function test_drafts_archived_offers_and_other_countries_cannot_be_reviewed(): void
    {
        $member = $this->member();
        $senegal = Country::factory()->create(['code' => 'SN']);

        foreach ([
            Offer::factory()->create(['status' => 'draft']),
            Offer::factory()->create(['status' => 'archived']),
            Offer::factory()->create(['status' => 'published', 'country_id' => $senegal->getKey()]),
        ] as $offer) {
            $this->review($member, $offer, 4, 'Bien.')->assertNotFound();
            $this->api($member, 'GET', "/api/v1/forms/offer.review?record={$offer->getKey()}")->assertNotFound();
        }

        $this->assertSame(0, OfferReview::query()->count());
    }

    public function test_reviews_of_an_archived_offer_are_shown_nowhere(): void
    {
        $owner = $this->member();
        $offer = Offer::factory()->for($owner)->create(['status' => 'published']);
        OfferReview::factory()->for($offer)->create(['rating' => 5, 'comment' => 'Super']);
        $offer->update(['status' => 'archived']);

        $this->assertNull($this->api($owner, 'GET', "/api/v1/offers/{$offer->getKey()}")->assertOk()->json('data.reviews'));
        $item = $this->api($owner, 'GET', '/api/v1/lists/offer.mine')->assertOk()->json('data.items.0');
        $this->assertNull($item['rating']);
        $this->assertStringNotContainsString('Super', (string) $item['highlight']);
    }

    public function test_an_offer_without_review_shows_no_rating_and_the_detail_lists_5_at_most(): void
    {
        $quiet = Offer::factory()->create(['status' => 'published']);
        $busy = Offer::factory()->create(['status' => 'published']);
        OfferReview::factory()->count(7)->for($busy)->create();

        $items = array_column($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.items'), null, 'id');
        $this->assertNull($items[(string) $quiet->getKey()]['rating']);
        $this->assertSame(7, $items[(string) $busy->getKey()]['rating']['count']);

        $reviews = $this->api(null, 'GET', "/api/v1/offers/{$busy->getKey()}")->json('data.reviews');
        $this->assertCount(5, $reviews['latest']);
        $this->assertNull($reviews['mine']);
        $this->assertFalse($reviews['can_review']);
        $this->assertNull($reviews['delete']);
        $this->assertSame(['average' => null, 'count' => 0, 'latest' => []], array_intersect_key(
            $this->api(null, 'GET', "/api/v1/offers/{$quiet->getKey()}")->json('data.reviews'),
            ['average' => 1, 'count' => 1, 'latest' => 1],
        ));
    }
}
