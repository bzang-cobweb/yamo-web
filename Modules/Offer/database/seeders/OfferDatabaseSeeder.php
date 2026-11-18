<?php

namespace Modules\Offer\Database\Seeders;

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
     * Crée l'arbre déclaré ; une catégorie existante (même slug) n'est ni
     * modifiée ni déplacée, ses enfants manquants sont ajoutés.
     *
     * @param  list<array{slug: string, name: array<string, string>, children?: list<mixed>}>  $categories
     */
    private function seed(array $categories, ?int $parentId): void
    {
        foreach ($categories as $position => $category) {
            $model = OfferCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'position' => $position, 'parent_id' => $parentId],
            );

            $this->seed($category['children'] ?? [], (int) $model->getKey());
        }
    }
}
