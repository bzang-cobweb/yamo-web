<?php

namespace Modules\Partner\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * Les partenaires de l'accueil, sans compte (décision 2026-11-23-module-partenaires).
 */
class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cameroon = Country::factory()->create(['code' => 'CM']);
    }

    private function partners(string $locale = 'fr')
    {
        return $this->withHeaders(['Accept-Language' => $locale, 'X-Country' => 'CM'])->getJson('/api/v1/partners');
    }

    public function test_only_visible_partners_of_the_country_with_their_texts_in_the_language(): void
    {
        $second = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Second'], 'position' => 2]);
        $first = Partner::factory()->for($this->cameroon)->create([
            'name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Wouri Movers'],
            'position' => 1,
            'description' => ['fr' => 'Déménagement clés en main.', 'en' => 'Turnkey moving.'],
            'url' => ['fr' => 'https://wouri.test/fr', 'en' => 'https://wouri.test/en'],
            'cta_label' => ['fr' => 'Demander un devis', 'en' => 'Get a quote'],
            'contact_email' => 'commercial@wouri.test',
        ]);
        // Pas publié, campagne pas commencée, autre pays : absents.
        Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);
        Partner::factory()->for($this->cameroon)->create(['starts_at' => now()->addDay()]);
        Partner::factory()->create();
        // Campagne en cours : présent.
        $running = Partner::factory()->for($this->cameroon)->create(['position' => 3, 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);

        $data = $this->partners()->assertOk()->json('data');

        // Au hasard (décision 2026-11-23-accueil-nombres-reglables) : les visibles, dans n'importe quel ordre.
        $this->assertEqualsCanonicalizing([(string) $first->getKey(), (string) $second->getKey(), (string) $running->getKey()], array_column($data, 'id'));
        $data = collect($data)->keyBy('id')->all();
        $this->assertSame([
            'id' => (string) $first->getKey(),
            'name' => 'Déménageurs du Wouri',
            'slogan' => null,
            'description' => 'Déménagement clés en main.',
            'url' => 'https://wouri.test/fr',
            'cta_label' => 'Demander un devis',
            'logo' => null,
        ], $data[(string) $first->getKey()]);
        // Ni contact ni compteurs dans l'app.
        $this->assertArrayNotHasKey('contact_email', $data[(string) $first->getKey()]);
        $this->assertArrayNotHasKey('views_count', $data[(string) $first->getKey()]);

        // Un nom et un lien par langue.
        $english = collect($this->partners('en')->json('data'))->firstWhere('id', (string) $first->getKey());
        $this->assertSame(['Wouri Movers', 'https://wouri.test/en'], [$english['name'], $english['url']]);
    }

    public function test_each_listing_counts_a_view_and_each_touch_a_click(): void
    {
        $partner = Partner::factory()->for($this->cameroon)->create();
        $hidden = Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);

        $this->partners()->assertOk();
        $this->partners()->assertOk();
        $this->withHeader('X-Country', 'CM')->postJson("/api/v1/partners/{$partner->getKey()}/click")->assertNoContent();

        $this->assertSame([2, 1], [$partner->fresh()->views_count, $partner->fresh()->clicks_count]);
        // Un partenaire invisible ne compte rien.
        $this->withHeader('X-Country', 'CM')->postJson("/api/v1/partners/{$hidden->getKey()}/click")->assertNotFound();
        $this->assertSame([0, 0], [$hidden->fresh()->views_count, $hidden->fresh()->clicks_count]);
    }

    public function test_as_many_partners_as_set_in_the_application_drawn_at_random(): void
    {
        // Décision 2026-11-23-accueil-nombres-reglables : 10 sans réglage, sinon le nombre réglé.
        Partner::factory()->count(12)->for($this->cameroon)->sequence(fn ($sequence) => ['position' => 12 - $sequence->index])->create();
        $this->assertCount(10, $this->partners()->json('data'));

        Setting::current()->update(['home_partners' => 3]);
        $draws = collect(range(1, 6))->map(fn (): array => array_column($this->partners()->json('data'), 'id'));
        $draws->each(fn (array $ids) => $this->assertCount(3, $ids));
        // Le tirage change d'un appel à l'autre.
        $this->assertGreaterThan(1, $draws->map(fn (array $ids): string => implode(',', $ids))->unique()->count());
        // Tirés au hasard, puis rangés dans l'ordre du back-office (décision 2026-11-23-partenaires-triables).
        $positions = Partner::query()->pluck('position', 'id');
        $draws->each(function (array $ids) use ($positions): void {
            $order = array_map(fn (string $id): int => (int) $positions[(int) $id], $ids);
            $sorted = $order;
            sort($sorted);
            $this->assertSame($sorted, $order);
        });
        // Seuls les partenaires servis comptent une vue.
        $this->assertSame(10 + 6 * 3, (int) Partner::query()->sum('views_count'));
    }

    public function test_the_expiry_date_hides_a_partner_only_when_partner_payment_is_applied(): void
    {
        // Décision 2026-11-23-paiement-partenaires, comme les offres.
        $paid = Partner::factory()->for($this->cameroon)->create(['expires_at' => now()->addDay()]);
        $expired = Partner::factory()->for($this->cameroon)->create(['expires_at' => now()->subMinute()]);
        $undated = Partner::factory()->for($this->cameroon)->create(['expires_at' => null]);
        $ids = fn (): array => array_column($this->partners()->json('data'), 'id');

        // Pas appliqué : la date est ignorée.
        $this->assertEqualsCanonicalizing(array_map('strval', [$paid->getKey(), $expired->getKey(), $undated->getKey()]), $ids());

        // Appliqué : jusqu'à la date seulement ; sans date, jamais.
        Setting::current()->update(['partner_payment_required' => true]);
        $this->assertSame([(string) $paid->getKey()], $ids());
        $this->withHeader('X-Country', 'CM')->postJson("/api/v1/partners/{$expired->getKey()}/click")->assertNotFound();
    }

    public function test_the_list_gives_every_visible_partner_in_back_office_order_and_the_detail_one(): void
    {
        // Décision 2026-11-23-partenaires-liste-detail.
        Setting::current()->update(['home_partners' => 1]);
        $c = Partner::factory()->for($this->cameroon)->create(['position' => 3]);
        $a = Partner::factory()->for($this->cameroon)->create(['position' => 1, 'name' => ['fr' => 'Assurances du Littoral']]);
        $b = Partner::factory()->for($this->cameroon)->create(['position' => 2]);
        $hidden = Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);
        $headers = ['Accept-Language' => 'fr', 'X-Country' => 'CM'];

        // Tous, au-delà du nombre de l'accueil, dans l'ordre, sans compte ; sans compter de vue ; page après page
        // comme toutes les listes.
        $this->assertSame(array_map('strval', [$a->getKey(), $b->getKey(), $c->getKey()]), array_column($this->withHeaders($headers)->getJson('/api/v1/lists/partner.all')->assertOk()->json('data.items'), 'id'));
        $this->assertSame(0, (int) Partner::query()->sum('views_count'));

        // Le détail d'un visible ; 404 pour un invisible ou inconnu.
        $this->withHeaders($headers)->getJson("/api/v1/partners/{$a->getKey()}")->assertOk()->assertJsonPath('data.name', 'Assurances du Littoral');
        // Sa page : son e-mail et son téléphone (décision 2026-11-24-partenaire-contact).
        $a->update(['contact_email' => 'contact@littoral.test', 'contact_phone' => '+237 6 70 00 00 02']);
        $this->withHeaders($headers)->getJson("/api/v1/partners/{$a->getKey()}")
            ->assertJsonPath('data.contact_email', 'contact@littoral.test')
            ->assertJsonPath('data.contact_phone', '+237 6 70 00 00 02');
        $this->withHeaders($headers)->getJson("/api/v1/partners/{$hidden->getKey()}")->assertNotFound();
        $this->withHeaders($headers)->getJson('/api/v1/partners/999999')->assertNotFound();
    }

    public function test_the_page_shows_the_status_to_the_partner_accounts_only(): void
    {
        // Décision 2026-11-24-partenaire-page-statut.
        $headers = ['Accept-Language' => 'fr', 'X-Country' => 'CM'];
        $member = User::factory()->create(['locale' => 'fr']);
        $member->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));
        $pending = Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);
        $shown = Partner::factory()->for($this->cameroon)->create();
        foreach ([$pending, $shown] as $partner) {
            $partner->users()->attach($member->getKey(), ['is_admin' => true]);
        }

        // Ses comptes : la pastille de « Mes partenaires ».
        $token = $member->createToken('iPhone')->plainTextToken;
        $this->withToken($token)->withHeaders($headers)->getJson("/api/v1/partners/{$pending->getKey()}")
            ->assertOk()->assertJsonPath('data.status', ['label' => 'En validation', 'tone' => 'warning']);
        $this->withToken($token)->withHeaders($headers)->getJson("/api/v1/partners/{$shown->getKey()}")
            ->assertJsonPath('data.status', ['label' => 'Affiché', 'tone' => 'success']);

        // Un visiteur : pas de pastille.
        $this->app['auth']->forgetGuards();
        $this->withoutToken()->withHeaders($headers)->getJson("/api/v1/partners/{$shown->getKey()}")->assertOk()->assertJsonMissingPath('data.status');
    }

    public function test_the_list_is_searched_by_name_or_description_in_every_language(): void
    {
        // Décision 2026-11-23-partenaires-recherche : sans casse, accents compris.
        $movers = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Wouri Movers'], 'description' => ['fr' => 'Cartons fournis.']]);
        $insurance = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Assurances du Littoral'], 'description' => ['fr' => 'Assurance habitation.']]);
        $search = fn (string $term): array => array_column($this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson('/api/v1/lists/partner.all?search='.urlencode($term))->assertOk()->json('data.items'), 'id');

        $this->assertSame([(string) $movers->getKey()], $search('déménageurs'));
        // Sous le nom, le slogan, sinon la description (décision 2026-11-24-mes-partenaires-slogan).
        $movers->setTranslation('slogan', 'fr', 'On porte, vous respirez.')->save();
        $items = collect($this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])->getJson('/api/v1/lists/partner.all')->json('data.items'))->keyBy('id');
        $this->assertSame('On porte, vous respirez.', $items[(string) $movers->getKey()]['subtitle']);
        $this->assertSame('Assurance habitation.', $items[(string) $insurance->getKey()]['subtitle']);
        $this->assertSame([(string) $movers->getKey()], $search('MOVERS'));
        $this->assertSame([(string) $insurance->getKey()], $search('habitation'));
        $this->assertSame([], $search('plomberie'));
        $this->assertCount(2, $search('  '));
    }

    public function test_the_list_comes_page_after_page(): void
    {
        // Le défilement infini de toutes les listes (décision 2026-11-23-partenaires-liste-detail).
        Partner::factory()->count(25)->for($this->cameroon)->create();
        $headers = ['Accept-Language' => 'fr', 'X-Country' => 'CM'];

        $first = $this->withHeaders($headers)->getJson('/api/v1/lists/partner.all')->assertOk()->json('data');
        $this->assertNotNull($first['next_cursor']);
        $next = $this->withHeaders($headers)->getJson('/api/v1/lists/partner.all?cursor='.urlencode($first['next_cursor']))->assertOk()->json('data');

        $ids = array_merge(array_column($first['items'], 'id'), array_column($next['items'], 'id'));
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertCount(25, $ids);
        $this->assertNull($next['next_cursor']);
    }

    public function test_become_a_partner_opens_the_form_or_the_sign_in(): void
    {
        // Décision 2026-11-24-devenir-partenaire.
        $headers = ['Accept-Language' => 'fr', 'X-Country' => 'CM'];
        $this->assertSame(['label' => 'Devenir partenaire', 'form' => null, 'link' => '/sign-in'], $this->withHeaders($headers)->getJson('/api/v1/lists/partner.all')->assertOk()->json('data.footer'));

        $member = User::factory()->create(['locale' => 'fr']);
        $member->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));
        $this->assertSame(['label' => 'Devenir partenaire', 'form' => 'partner.partner', 'link' => null], $this->withHeaders($headers)->withToken($member->createToken('iPhone')->plainTextToken)->getJson('/api/v1/lists/partner.all')->assertOk()->json('data.footer'));
    }

    public function test_no_partner_an_empty_list(): void
    {
        $this->partners()->assertOk()->assertExactJson(['data' => []]);
    }
}
