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

    private function category(string $slug): OfferCategory
    {
        return OfferCategory::query()->where('slug', $slug)->sole();
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

        $this->assertSame(['bureau', 'logement'], OfferCategory::query()->roots()->orderBy('position')->pluck('slug')->all());

        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $this->category('logement'), 'pageClass' => EditOfferCategory::class])
            ->assertCanSeeTableRecords([$this->category('appartement'), $this->category('maison')])
            ->assertCanNotSeeTableRecords([$this->category('studio')])
            ->call('reorderTable', [(string) $this->category('maison')->getKey(), (string) $this->category('appartement')->getKey()]);

        $this->assertSame(['maison', 'appartement'], $this->category('logement')->children()->pluck('slug')->all());
    }

    public function test_a_child_is_created_at_the_end_of_its_siblings(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(ChildrenRelationManager::class, ['ownerRecord' => $this->category('logement'), 'pageClass' => EditOfferCategory::class])
            ->callTableAction('create', data: ['name' => ['fr' => 'Chambre', 'en' => 'Room'], 'slug' => 'chambre'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(['appartement', 'maison', 'chambre'], $this->category('logement')->children()->pluck('slug')->all());
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
        $this->assertNotNull(OfferCategory::query()->where('slug', 'appartement')->first());

        // Suppression groupée : refusée aussi dès qu'une catégorie cochée a des enfants.
        Livewire::test(ListOfferCategories::class)
            ->callTableBulkAction('delete', [$this->category('bureau'), $this->category('logement')])
            ->assertNotified(__('offer::offer.category_resource.has_children'));
        $this->assertSame(5, OfferCategory::query()->count());

        Livewire::test(EditOfferCategory::class, ['record' => $this->category('studio')->getKey()])->callAction('delete');
        $this->assertNull(OfferCategory::query()->where('slug', 'studio')->first());
    }

    public function test_creating_a_category_under_a_parent(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_OFFERS, PermissionEnum::UPDATE_OFFERS);

        Livewire::test(CreateOfferCategory::class)
            ->fillForm(['name.fr' => 'Loft', 'name.en' => 'Loft', 'slug' => 'loft', 'parent_id' => (string) $this->category('appartement')->getKey()])
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
            ['slug' => 'logement', 'name' => ['fr' => 'Logement', 'en' => 'Home'], 'children' => [
                ['slug' => 'appartement', 'name' => ['fr' => 'Appartement', 'en' => 'Flat'], 'children' => [
                    ['slug' => 'studio', 'name' => ['fr' => 'Studio', 'en' => 'Studio']],
                ]],
                ['slug' => 'maison', 'name' => ['fr' => 'Maison', 'en' => 'House']],
            ]],
            ['slug' => 'bureau', 'name' => ['fr' => 'Bureau', 'en' => 'Office']],
        ];
    }
}
