<?php

namespace Modules\Offer\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use LogicException;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Form\Support\Field;
use Modules\Location\Models\Country;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Models\Offer;

/**
 * Offres multilingues (décision 2026-11-18-offres-multilingues-categories-arbre) :
 * saisie dans la langue du compte, repli à la lecture, attributs traduisibles,
 * onglets du back-office, conversion des offres existantes.
 */
class TranslationTest extends AuthFilamentTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(DomainRegistry::class)->register(TestNotesDomain::class);
        config(['offer.domain' => 'test.notes']);
        // L'espace pays des tests : la Suisse, seul pays actif (décision 2026-11-18-espace-pays).
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
        // L'API par défaut ; le test du back-office le rallume.
        Filament::setServingStatus(false);
    }

    private function member(string $locale): User
    {
        $user = User::factory()->create(['locale' => $locale]);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        // Un autre compte dans le même test : le compte de la requête précédente est oublié.
        app('auth')->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', $user->locale);
    }

    public function test_texts_are_saved_in_the_account_language_and_read_with_a_fallback(): void
    {
        $english = $this->member('en');
        $this->asUser($english)->postJson('/api/v1/offers', ['title' => 'Bright flat', 'description' => 'Near the lake.', 'status' => 'draft', 'note' => 'Quiet street'])->assertNoContent();

        $offer = Offer::query()->sole();
        $this->assertSame(['en' => 'Bright flat'], $offer->getTranslations('title'));
        $this->assertSame(['en' => 'Quiet street'], $offer->detail('note'));

        // Un lecteur francophone voit l'anglais tant qu'il n'y a pas de français.
        $this->assertSame('Bright flat', $offer->getTranslation('title', 'fr'));
        $offer->update(['user_id' => $this->member('fr')->getKey()]);
        $french = $offer->user;
        $this->assertSame('Bright flat', $this->asUser($french)->getJson('/api/v1/lists/offer.mine')->json('data.items.0.title'));
        $defaults = array_column($this->asUser($french)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data.fields'), 'default', 'name');
        $this->assertSame(['Bright flat', 'Quiet street'], [$defaults['title'], $defaults['note']]);
    }

    public function test_a_member_update_keeps_the_other_languages(): void
    {
        $french = $this->member('fr');
        $offer = Offer::factory()->for($french)->create(['title' => ['fr' => 'Appartement', 'en' => 'Flat'], 'details' => ['note' => ['fr' => 'Calme', 'en' => 'Quiet']]]);

        $this->asUser($french)->patchJson("/api/v1/offers/{$offer->getKey()}", ['title' => 'Grand appartement', 'status' => 'draft', 'note' => 'Très calme'])->assertNoContent();

        $this->assertSame(['fr' => 'Grand appartement', 'en' => 'Flat'], $offer->fresh()->getTranslations('title'));
        $this->assertSame(['fr' => 'Très calme', 'en' => 'Quiet'], $offer->fresh()->detail('note'));
        // Le français vidé : il est retiré, l'anglais reste.
        $this->asUser($french)->patchJson("/api/v1/offers/{$offer->getKey()}", ['title' => 'Grand appartement', 'status' => 'draft', 'note' => ''])->assertNoContent();
        $this->assertSame(['en' => 'Quiet'], $offer->fresh()->detail('note'));
    }

    public function test_the_search_looks_in_every_language(): void
    {
        $french = $this->member('fr');
        $flat = Offer::factory()->for($french)->create(['title' => ['fr' => 'Appartement', 'en' => 'Sunny flat']]);
        Offer::factory()->for($french)->create(['title' => ['fr' => 'Maison', 'en' => 'House']]);

        $this->assertSame([(string) $flat->getKey()], array_column($this->asUser($french)->getJson('/api/v1/lists/offer.mine?search=sunny')->json('data.items'), 'id'));
        // Le nom d'une langue (« fr ») n'est pas un texte cherché.
        $this->assertSame([], $this->asUser($french)->getJson('/api/v1/lists/offer.mine?search=fr')->json('data.items'));
    }

    public function test_the_back_office_edits_a_translatable_attribute_by_language(): void
    {
        Filament::setServingStatus();
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $offer = Offer::factory()->create(['title' => ['fr' => 'Appartement'], 'details' => ['note' => ['fr' => 'Calme']]]);

        Livewire::test(EditOffer::class, ['record' => $offer->getKey()])
            ->assertSchemaStateSet(['details.note.fr' => 'Calme', 'title.fr' => 'Appartement'])
            ->fillForm(['details.note.en' => 'Quiet', 'title.en' => 'Flat'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['fr' => 'Calme', 'en' => 'Quiet'], $offer->fresh()->detail('note'));
        $this->assertSame(['fr' => 'Appartement', 'en' => 'Flat'], $offer->fresh()->getTranslations('title'));
    }

    public function test_a_domain_without_steps_keeps_a_single_page_form(): void
    {
        $this->assertSame([], $this->asUser($this->member('fr'))->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data.steps'));
    }

    public function test_declared_steps_keep_every_field_and_drop_empty_ones(): void
    {
        app(DomainRegistry::class)->register(TestStepsDomain::class);
        config(['offer.domain' => 'test.steps']);

        $steps = $this->asUser($this->member('fr'))->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data.steps');

        // L'étape qui ne cite que le prix (absent de ce domaine) disparaît.
        $this->assertSame(['La note', "L'annonce"], array_column($steps, 'title'));
        $this->assertSame(['note'], $steps[0]['fields']);
        // Les champs qu'aucune étape ne cite rejoignent la dernière.
        $this->assertSame('title', $steps[1]['fields'][0]);
        $this->assertContains('description', $steps[1]['fields']);
        $this->assertContains('status', $steps[1]['fields']);
    }

    public function test_only_texts_can_be_translatable(): void
    {
        $this->expectException(LogicException::class);

        Field::number('surface')->translatable();
    }

    public function test_existing_offers_are_converted_and_back(): void
    {
        $migration = require module_path('Offer', 'database/migrations/2026_11_23_110000_make_offer_texts_translatable.php');
        $migration->down();
        $user = User::factory()->create();
        DB::table('offers')->insert(['user_id' => $user->getKey(), 'title' => 'Studio', 'description' => null, 'status' => 'draft', 'details' => '[]']);

        $migration->up();
        $this->assertSame(['fr' => 'Studio'], Offer::query()->sole()->getTranslations('title'));

        Offer::query()->sole()->setTranslation('title', 'en', 'Studio flat')->save();
        $migration->down();
        $this->assertSame('Studio', DB::table('offers')->value('title'));
        $migration->up();
    }
}

/** Domaine de test : un attribut traduisible, sans prix. */
class TestNotesDomain extends Domain
{
    public static function key(): string
    {
        return 'test.notes';
    }

    public function label(): string
    {
        return 'Annonce';
    }

    public function pluralLabel(): string
    {
        return 'Annonces';
    }

    public function mineLabel(): string
    {
        return 'Mes annonces';
    }

    public function attributes(): array
    {
        return [Field::text('note')->label('Note')->translatable()->maxLength(255)];
    }

    public function hasPrice(): bool
    {
        return false;
    }
}

/** Domaine de test : des étapes, dont une sans champ présent. */
class TestStepsDomain extends TestNotesDomain
{
    public static function key(): string
    {
        return 'test.steps';
    }

    public function formSteps(): array
    {
        return [
            ['title' => 'La note', 'fields' => ['note', 'price']],
            ['title' => 'Le prix', 'fields' => ['price']],
            ['title' => "L'annonce", 'fields' => ['title']],
        ];
    }
}
