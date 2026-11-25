<?php

namespace Modules\Offer\Tests\Feature\Filament;

use Livewire\Livewire;
use LogicException;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\CreateOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\EditOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\ListOfferCategories;
use Modules\Offer\Filament\Resources\OfferCategoryResource\RelationManagers\ChildrenRelationManager;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories en arbre, triables (décision 2026-11-18-offres-multilingues-categories-arbre).
 */
class CategoryTreeTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(DomainRegistry::class)->register(TreeDomain::class);
        config(['offer.domain' => 'test.tree']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    /** Par son slug français, tiré du nom (décision 2026-11-25-categories-slug-traduit). */
    private function category(string $slug): OfferCategory
    {
        return OfferCategory::query()->withSlug($slug)->sole();
    }

    /**
     * Les slugs français, dans l'ordre.
     *
     * @param  iterable<OfferCategory>  $categories
     * @return list<string>
     */
    private function slugs(iterable $categories): array
    {
        return collect($categories)->map(fn (OfferCategory $category): string => (string) $category->getTranslation('slug', 'fr'))->values()->all();
    }

    public function test_the_domain_tree_is_seeded_in_order_and_replaying_changes_nothing(): void
    {
        $this->assertSame(
            ['Logement', 'Logement › Appartement', 'Logement › Appartement › Studio', 'Logement › Maison', 'Bureau'],
            array_values(OfferCategory::options()),
        );

        // Déplacée à la main, puis seeder rejoué : rien n'est recréé ni remis en place.
        $this->category('maison')->update(['parent_id' => $this->category('bureau')->getKey()]);
        $this->seed(OfferDatabaseSeeder::class);

        $this->assertSame(5, OfferCategory::query()->count());
        $this->assertSame('Bureau › Maison', $this->category('maison')->path());
    }

    public function test_the_list_shows_roots_sortable_and_a_category_shows_its_children(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(ListOfferCategories::class)
            ->assertCanSeeTableRecords([$this->category('logement'), $this->category('bureau')])
            ->assertCanNotSeeTableRecords([$this->category('appartement')])
            ->call('reorderTable', [(string) $this->category('bureau')->getKey(), (string) $this->category('logement')->getKey()]);

        $this->assertSame(['bureau', 'logement'], $this->slugs(OfferCategory::query()->roots()->orderBy('position')->get()));

        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $this->category('logement'), 'pageClass' => EditOfferCategory::class])
            ->assertCanSeeTableRecords([$this->category('appartement'), $this->category('maison')])
            ->assertCanNotSeeTableRecords([$this->category('studio')])
            ->call('reorderTable', [(string) $this->category('maison')->getKey(), (string) $this->category('appartement')->getKey()]);

        $this->assertSame(['maison', 'appartement'], $this->slugs($this->category('logement')->children()->get()));
    }

    public function test_a_child_is_created_at_the_end_of_its_siblings(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $this->category('logement'), 'pageClass' => EditOfferCategory::class])
            ->callTableAction('create', data: ['name' => ['fr' => 'Chambre', 'en' => 'Room']])
            ->assertHasNoTableActionErrors();

        $this->assertSame(['appartement', 'maison', 'chambre'], $this->slugs($this->category('logement')->children()->get()));
        // Le slug de chaque langue, tiré de son nom.
        $this->assertSame(['fr' => 'chambre', 'en' => 'room'], array_intersect_key($this->category('chambre')->getTranslations('slug'), ['fr' => 1, 'en' => 1]));
    }

    public function test_moving_a_category_refuses_a_loop(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        $logement = $this->category('logement');

        // Ni elle-même ni un descendant ne sont proposés comme parent.
        $this->assertSame(['Bureau'], array_values(OfferCategory::options(except: $logement)));

        Livewire::test(EditOfferCategory::class, ['record' => $logement->getKey()])
            ->fillForm(['parent_id' => (string) $this->category('studio')->getKey()])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);

        $this->expectException(LogicException::class);
        $logement->update(['parent_id' => $this->category('studio')->getKey()]);
    }

    public function test_a_category_with_children_cannot_be_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(EditOfferCategory::class, ['record' => $this->category('appartement')->getKey()])
            ->callAction('delete')
            ->assertNotified(__('offer::offer.category_resource.has_children'));
        $this->assertNotNull(OfferCategory::query()->withSlug('appartement')->first());

        // Suppression groupée : refusée aussi dès qu'une catégorie cochée a des enfants.
        Livewire::test(ListOfferCategories::class)
            ->callTableBulkAction('delete', [$this->category('bureau'), $this->category('logement')])
            ->assertNotified(__('offer::offer.category_resource.has_children'));
        $this->assertSame(5, OfferCategory::query()->count());

        Livewire::test(EditOfferCategory::class, ['record' => $this->category('studio')->getKey()])->callAction('delete');
        $this->assertNull(OfferCategory::query()->withSlug('studio')->first());
    }

    public function test_a_slug_is_unique_among_siblings_only(): void
    {
        // Décision 2026-11-25-categories-slug-traduit : « Studio » sous « Maison » aussi, son slug reste « studio » ;
        // deux sœurs du même nom ont des slugs distincts.
        $other = OfferCategory::query()->create(['name' => ['fr' => 'Studio', 'en' => 'Studio'], 'parent_id' => $this->category('maison')->getKey(), 'position' => 0]);
        $this->assertSame('studio', $other->getTranslation('slug', 'fr'));
        $twin = OfferCategory::query()->create(['name' => ['fr' => 'Studio', 'en' => 'Studio'], 'parent_id' => $this->category('maison')->getKey(), 'position' => 1]);
        $this->assertNotSame('studio', $twin->getTranslation('slug', 'fr'));

        // Au back-office : pas de slug à la création ; à la modification, en lecture seule, et il ne suit pas le nom.
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);
        Livewire::test(CreateOfferCategory::class)->assertFormFieldDoesNotExist('slug.fr');
        $maison = $this->category('maison');
        Livewire::test(EditOfferCategory::class, ['record' => $maison->getKey()])
            ->assertSee('Tiré du nom à la création')
            ->assertSchemaStateSet(['slug.fr' => 'maison'])
            ->fillForm(['name.fr' => 'Villa'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['Villa', 'maison'], [$maison->fresh()->getTranslation('name', 'fr'), $maison->fresh()->getTranslation('slug', 'fr')]);
    }

    public function test_creating_a_category_under_a_parent(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(CreateOfferCategory::class)
            ->fillForm(['name.fr' => 'Loft', 'name.en' => 'Loft', 'parent_id' => (string) $this->category('appartement')->getKey()])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Logement › Appartement › Loft', $this->category('loft')->path());
        // Après « Studio » (position 0).
        $this->assertSame(1, $this->category('loft')->position);
    }
}

/** Domaine de test : un arbre de trois niveaux. */
class TreeDomain extends Domain
{
    public static function key(): string
    {
        return 'test.tree';
    }

    public function label(): string
    {
        return 'Bien';
    }

    public function pluralLabel(): string
    {
        return 'Biens';
    }

    public function mineLabel(): string
    {
        return 'Mes biens';
    }

    public function attributes(): array
    {
        return [];
    }

    public function categories(): array
    {
        return [
            ['name' => ['fr' => 'Logement', 'en' => 'Home'], 'children' => [
                ['name' => ['fr' => 'Appartement', 'en' => 'Flat'], 'children' => [
                    ['name' => ['fr' => 'Studio', 'en' => 'Studio']],
                ]],
                ['name' => ['fr' => 'Maison', 'en' => 'House']],
            ]],
            ['name' => ['fr' => 'Bureau', 'en' => 'Office']],
        ];
    }
}
