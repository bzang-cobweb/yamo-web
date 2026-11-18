<?php

namespace Modules\Offer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories de départ du domaine actif, créées si elles manquent. Rejouable :
 * une catégorie existante (même slug) n'est pas modifiée. Sans domaine, rien.
 */
class OfferDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $domain = app(DomainRegistry::class)->active();

        foreach ($domain?->categories() ?? [] as $position => $category) {
            OfferCategory::query()->firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'position' => $position]);
        }
    }
}
