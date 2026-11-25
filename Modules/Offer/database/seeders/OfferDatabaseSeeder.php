<?php

namespace Modules\Offer\Database\Seeders;

use App\Support\Locales;
use Illuminate\Database\Seeder;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories de départ du domaine actif, en arbre, créées si elles manquent.
 * Rejouable : une catégorie existante (même slug) n'est ni modifiée ni déplacée.
 * Sans domaine, rien.
 */
class OfferDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $domain = app(DomainRegistry::class)->active();

        $this->seed($domain?->categories() ?? [], null);
    }

    /**
     * Crée l'arbre déclaré ; une catégorie existante (même nom dans la langue par défaut, sous ce parent ou déplacée
     * ailleurs) n'est ni modifiée ni déplacée, ses enfants manquants sont ajoutés. Les slugs, traduits, sont tirés des noms (décision
     * 2026-11-25-categories-slug-traduit).
     *
     * @param  list<array{name: array<string, string>, children?: list<mixed>}>  $categories
     */
    private function seed(array $categories, ?int $parentId): void
    {
        foreach ($categories as $position => $category) {
            $name = (string) ($category['name'][Locales::default()] ?? reset($category['name']));
            // Sous son parent d'abord ; sinon où qu'elle soit (déplacée à la main) ; sinon créée.
            $model = OfferCategory::query()->where('parent_id', $parentId)->where('name->'.Locales::default(), $name)->first()
                ?? OfferCategory::query()->where('name->'.Locales::default(), $name)->first()
                ?? OfferCategory::query()->create(['name' => $category['name'], 'position' => $position, 'parent_id' => $parentId]);

            $this->seed($category['children'] ?? [], (int) $model->getKey());
        }
    }
}
