<?php

namespace Modules\Offer\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\WishlistService;
use Tests\TestCase;

/**
 * Qui voit une offre (décision 2026-11-19-contrat-paiement-visibilite-avis) : son
 * auteur toujours ; les autres, si elle est publiée, du pays courant et, le paiement
 * appliqué, pas expirée. La même règle pour la fiche, l'accueil, les villes, les
 * favoris, la liste des avis, donner un avis et mettre en favori.
 */
class OfferVisibilityApiTest extends TestCase
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

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function api(?User $user, string $method, string $uri, array $data = []): TestResponse
    {
        app('auth')->forgetGuards();
        $this->flushHeaders();
        $request = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);

        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri, $data);
    }

    private function home(User $owner, string $city, mixed $expiresAt): Offer
    {
        // Une seule région : la recherche en exige une (décision 2026-11-25-region-obligatoire-recherche).
        return Offer::factory()->for($owner)->at(Address::factory()->create(['city' => $city, 'region_id' => $this->regionOf($this->cameroon)]))
            ->create(['status' => 'published', 'country_id' => $this->cameroon->getKey(), 'expires_at' => $expiresAt, 'details' => ['property_type' => 'studio']]);
    }

    /**
     * @return list<string>
     */
    private function browse(?User $user = null): array
    {
        return array_column($this->api($user, 'GET', '/api/v1/lists/offer.browse')->assertOk()->json('data.items'), 'id');
    }

    /**
     * @return list<string>
     */
    private function cities(): array
    {
        $filters = array_column($this->api(null, 'GET', '/api/v1/lists/offer.browse')->json('data.filters'), null, 'name');

        return array_column($filters['city']['options'], 'value');
    }

    public function test_without_payment_the_expiration_date_has_no_effect(): void
    {
        $owner = $this->member();
        $expired = $this->home($owner, 'Kribi', now()->subDay());
        $never = $this->home($owner, 'Limbé', null);

        $this->assertFalse(Setting::paymentRequired());
        $this->api(null, 'GET', "/api/v1/offers/{$expired->getKey()}")->assertOk();
        $this->api(null, 'GET', "/api/v1/offers/{$never->getKey()}")->assertOk();
        $this->assertEqualsCanonicalizing([(string) $expired->getKey(), (string) $never->getKey()], $this->browse());
    }

    public function test_with_payment_only_offers_not_yet_expired_are_visible_to_others(): void
    {
        Setting::current()->update(['payment_required' => true]);
        $owner = $this->member();
        $visitor = $this->member();
        $paid = $this->home($owner, 'Douala', now()->addMonth());
        $expired = $this->home($owner, 'Kribi', now()->subDay());
        $never = $this->home($owner, 'Limbé', null);
        // Dans sa liste de favoris (décision 2026-11-27-listes-de-favoris).
        $list = app(WishlistService::class)->add($visitor, $paid);
        $list->items()->create(['offer_id' => $expired->getKey()]);

        // Fiche : 404 pour un visiteur et un autre membre ; l'auteur la voit toujours.
        $this->api(null, 'GET', "/api/v1/offers/{$paid->getKey()}")->assertOk();
        foreach ([$expired, $never] as $hidden) {
            $this->api(null, 'GET', "/api/v1/offers/{$hidden->getKey()}")->assertNotFound();
            $this->api($visitor, 'GET', "/api/v1/offers/{$hidden->getKey()}")->assertNotFound();
            $this->api($owner, 'GET', "/api/v1/offers/{$hidden->getKey()}")->assertOk();
        }

        // Accueil, villes, favoris : seulement celle qui n'a pas expiré.
        $this->assertSame([(string) $paid->getKey()], $this->browse());
        $this->assertSame([(string) $paid->getKey()], $this->browse($owner));
        $this->assertSame(['Douala'], $this->cities());
        $favorites = $this->api($visitor, 'GET', '/api/v1/lists/offer.wishlist?filter[wishlist]='.$list->getKey())->assertOk()->json('data.items');
        $this->assertSame([(string) $paid->getKey()], array_column($favorites, 'id'));

        // Avis et favori : 404 sur une offre expirée.
        $this->api(null, 'GET', "/api/v1/offers/{$expired->getKey()}/reviews")->assertNotFound();
        $this->api(null, 'GET', "/api/v1/offers/{$paid->getKey()}/reviews")->assertOk();
        $this->api($visitor, 'PUT', "/api/v1/offers/{$expired->getKey()}/review", ['rating' => 5, 'comment' => 'Très bien.'])->assertNotFound();
        $this->api($visitor, 'PUT', "/api/v1/offers/{$expired->getKey()}/favorite")->assertNotFound();
        $this->api($visitor, 'PUT', "/api/v1/offers/{$paid->getKey()}/favorite")->assertNoContent();

        // « Mes logements » : l'auteur garde toutes les siennes.
        $this->assertCount(3, $this->api($owner, 'GET', '/api/v1/lists/offer.mine')->assertOk()->json('data.items'));
    }

    public function test_with_payment_an_offer_is_visible_only_from_its_start_date(): void
    {
        // Décision 2026-11-23-offre-paye-a-partir-du.
        $owner = $this->member();
        $started = $this->home($owner, 'Douala', now()->addMonth());
        $started->update(['starts_at' => now()->subDay()]);
        $upcoming = $this->home($owner, 'Kribi', now()->addMonth());
        $upcoming->update(['starts_at' => now()->addDay()]);

        // Sans paiement, le début n'a pas d'effet.
        $this->assertEqualsCanonicalizing([(string) $started->getKey(), (string) $upcoming->getKey()], $this->browse());

        // Avec paiement : à partir du début seulement ; l'auteur la voit toujours.
        Setting::current()->update(['payment_required' => true]);
        $this->assertSame([(string) $started->getKey()], $this->browse());
        $this->api(null, 'GET', "/api/v1/offers/{$upcoming->getKey()}")->assertNotFound();
        $this->api($owner, 'GET', "/api/v1/offers/{$upcoming->getKey()}")->assertOk();

        // « Mes logements » : « Payé à partir du … » tant que le début est à venir.
        $items = collect($this->api($owner, 'GET', '/api/v1/lists/offer.mine')->json('data.items'))->keyBy('id');
        $this->assertStringStartsWith('Payé à partir du ', (string) $items[(string) $upcoming->getKey()]['highlight']);
        $this->assertStringNotContainsString('Payé à partir du', (string) $items[(string) $started->getKey()]['highlight']);
    }
}
