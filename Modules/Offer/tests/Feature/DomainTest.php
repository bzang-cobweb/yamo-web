<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Form\Models\FormUpload;
use Modules\Form\Support\Field;
use Modules\Form\Tests\Concerns\MakesFormUploads;
use Modules\Location\Models\Country;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * Le domaine actif change l'offre sans toucher au socle ; sans domaine, rien
 * n'est exposé (décision 2026-11-18-offres-et-domaines).
 */
class DomainTest extends TestCase
{
    use MakesFormUploads, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(FormUpload::DISK);
        Storage::fake('public');

        // L'espace pays des tests : un seul pays actif (décision 2026-11-18-espace-pays).
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_without_a_domain_nothing_about_offers_exists(): void
    {
        config(['offer.domain' => null]);
        $user = $this->member();

        $this->asUser($user)->getJson('/api/v1/forms/offer.offer')->assertNotFound();
        $this->asUser($user)->getJson('/api/v1/lists/offer.mine')->assertNotFound();
        $this->asUser($user)->postJson('/api/v1/offers', ['title' => 'x'])->assertNotFound();
        $this->assertNotContains('offers', array_column($this->asUser($user)->getJson('/api/v1/auth/me/menu')->json('data'), 'key'));
    }

    public function test_an_unknown_domain_is_a_clear_error(): void
    {
        config(['offer.domain' => 'castle']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('castle');

        app(DomainRegistry::class)->active();
    }

    public function test_a_domain_cannot_reuse_a_common_field_name(): void
    {
        $this->expectException(LogicException::class);

        app(DomainRegistry::class)->register(ClashingDomain::class);
    }

    public function test_another_domain_changes_the_form_validation_and_labels_without_touching_the_core(): void
    {
        app(DomainRegistry::class)->register(TestShopDomain::class);
        config(['offer.domain' => 'test.shop']);
        $user = $this->member();

        $fields = array_column($this->asUser($user)->getJson('/api/v1/forms/offer.offer')->assertOk()
            ->assertJsonPath('data.title', 'Ajouter : Article')
            ->json('data.fields'), null, 'name');
        // Pas d'adresse dans une boutique ; ses propres attributs.
        $this->assertArrayNotHasKey('address_id', $fields);
        // Les attributs du domaine, puis les photos, communes à tous (décision 2026-11-19-contrat-champ-upload-photos).
        $this->assertSame(['size', 'stock', 'photos'], array_slice(array_keys($fields), -3));
        $this->assertSame('Mes articles', $this->asUser($user)->getJson('/api/v1/lists/offer.mine')->json('data.title'));

        $this->asUser($user)->postJson('/api/v1/offers', ['title' => 'T-shirt', 'status' => 'draft', 'price' => '20', 'size' => 'XXL', 'stock' => '-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size', 'stock']);

        $this->asUser($user)->postJson('/api/v1/offers', ['title' => 'T-shirt', 'status' => 'draft', 'price' => '20', 'size' => 'M', 'stock' => '5', 'photos' => [$this->pendingUpload($user)]])->assertNoContent();
        $this->assertSame(['size' => 'M', 'stock' => 5], Offer::query()->sole()->details);
    }

    public function test_one_offer_per_account_when_the_domain_says_so(): void
    {
        app(DomainRegistry::class)->register(TestProfileDomain::class);
        config(['offer.domain' => 'test.profile']);
        $user = $this->member();

        $this->asUser($user)->postJson('/api/v1/offers', ['title' => 'Moi', 'status' => 'published', 'photos' => [$this->pendingUpload($user)]])->assertNoContent();
        $this->assertNull($this->asUser($user)->getJson('/api/v1/lists/offer.mine')->json('data.create'));
        $this->asUser($user)->postJson('/api/v1/offers', ['title' => 'Encore moi', 'status' => 'published', 'photos' => [$this->pendingUpload($user)]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}

/** Domaine de test : une boutique, sans adresse. */
class TestShopDomain extends Domain
{
    public static function key(): string
    {
        return 'test.shop';
    }

    public function label(): string
    {
        return 'Article';
    }

    public function pluralLabel(): string
    {
        return 'Articles';
    }

    public function mineLabel(): string
    {
        return 'Mes articles';
    }

    public function attributes(): array
    {
        return [
            Field::select('size')->label('Taille')->options(['S' => 'S', 'M' => 'M', 'L' => 'L'])->required(),
            Field::number('stock')->label('Stock')->required()->rules(['integer', 'min:0']),
        ];
    }
}

/** Domaine de test : un seul profil par compte, sans prix. */
class TestProfileDomain extends Domain
{
    public static function key(): string
    {
        return 'test.profile';
    }

    public function label(): string
    {
        return 'Profil';
    }

    public function pluralLabel(): string
    {
        return 'Profils';
    }

    public function mineLabel(): string
    {
        return 'Mon profil';
    }

    public function attributes(): array
    {
        return [];
    }

    public function hasPrice(): bool
    {
        return false;
    }

    public function onePerAccount(): bool
    {
        return true;
    }
}

/** Domaine invalide : un attribut au nom d'un champ commun. */
class ClashingDomain extends TestProfileDomain
{
    public static function key(): string
    {
        return 'test.clash';
    }

    public function attributes(): array
    {
        return [Field::text('title')];
    }
}
